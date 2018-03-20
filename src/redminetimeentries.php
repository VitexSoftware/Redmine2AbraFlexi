<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

$oPage = new ui\WebPage('Redmine2FlexiBee: Obtain Time Entries');


$projects = $oPage->getRequestValue('project');
$start    = $oPage->getRequestValue('startdate');
$end      = $oPage->getRequestValue('enddate');

if (empty($projects)) {
    $oPage->addStatusMessage(_('Please Select some projects to import'));
    $oPage->redirect('redmineprojects.php');
} else {
    \Ease\Shared::instanced()->loadConfig('../config.json');

    $invoicer = new FakturaVydana();
    $redminer = new RedmineRestClient();
    if (count($projects) == 1) {
        $projectInfo = $redminer->getProjectInfo(key($projects),
            ['include' => 'time_entry_activities']); //since redmine 3.4.0
        if (array_key_exists('custom_fields', $projectInfo)) {
            foreach ($projectInfo['custom_fields'] as $customFieldInfo) {
                if ($customFieldInfo['name'] == 'FlexiBee Firma') {
                    $invoicer->setDataValue('firma',
                        \FlexiPeeHP\FlexiBeeRO::code($customFieldInfo['value']));
                }
            }
        }
    }

    $pricelister = new \FlexiPeeHP\Cenik('code:'.constant('FLEXIBEE_CENIK'));
    if ($pricelister->lastResponseCode == 404) {
        $pricelister->insertToFlexiBee(['code' => constant('FLEXIBEE_CENIK'), 'nazev' => constant('FLEXIBEE_CENIK'),
            'typZasobyK' => 'typZasoby.sluzba', 'skladove' => false, 'cenaZakl' => 1,
            'cenaZaklBezDph' => 1]);
    }

    foreach (array_keys($projects) as $projectID) {
//        if (array_key_exists('time_entry_activities', $projectInfo)) {
//            $items = $projectInfo['time_entry_activities'];
//        } else {
        $items = $redminer->getTimeEntries($projectID, $start, $end);
//        }

        if (!empty($items)) {
            $invoicer->takeItemsFromArray($items);
        }
    }

    $created = $invoicer->sync();

    $invoiceTabs = new \Ease\TWB\Tabs('Invoices');

    $invoiceTabs->addTab(_('Html'),
        new \FlexiPeeHP\ui\EmbedResponsiveHTML($invoicer));
    $invoiceTabs->addTab(_('PDF'),
        new \FlexiPeeHP\ui\EmbedResponsivePDF($invoicer));

    $oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel('Doklad '.new \Ease\Html\ATag($invoicer->getApiUrl(),
                    $invoicer->getDataValue('kod')).' '.($created ? 'byl' : 'nebyl').' vystaven',
                $created ? 'success' : 'danger', $invoiceTabs,
                $oPage->getStatusMessagesAsHtml())));
    $oPage->draw();
}

