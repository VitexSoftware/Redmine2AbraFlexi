<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';
new \Ease\Locale('cs_CZ', '../i18n', 'redmine2flexibee');
session_start();

$oPage  = new ui\WebPage('Redmine2FlexiBee: Obtain Time Entries');
$userID = $oPage->getRequestValue('userid', 'int');

$typFak = $oPage->getRequestValue('typ-faktury-vydane');
if (empty($typFak)) {
    $oPage->redirect('redmineprojects.php');
}

$projects = $oPage->getRequestValue('project');
$start    = $oPage->getRequestValue('startdate');
$end      = $oPage->getRequestValue('enddate');
$firma    = $oPage->getRequestValue('firma');

if (empty($projects)) {
    $oPage->addStatusMessage(_('Please Select some projects to import'));
    $oPage->redirect('redmineprojects.php');
} else {
    \Ease\Shared::instanced()->loadConfig('../config.json', true);


    $timesheetParams = [
        "utf8" => "✓",
        "timesheet[period]" => "all",
        "timesheet[period_type]" => "2",
        "timesheet[date_from]" => $start,
        "timesheet[date_to]" => $end,
        "timesheet[sort]" => "project",
        "timesheet[projects][]" => implode(',', array_keys($projects)),
        "timesheet[users][]" => $userID,
        "commit" => "Použít"
    ];

    $oPage->addItem(new \Ease\TWB\LinkButton(constant('REDMINE_URL').'/timesheet/report/?'.http_build_query($timesheetParams),
            _('Timesheet'), 'info', ['target' => 'blank']));

    $invoicer = new FakturaVydana([
        'typDokl' => \FlexiPeeHP\FlexiBeeRO::code($typFak),
        'firma' => \FlexiPeeHP\FlexiBeeRO::code($firma),
        'popis' => sprintf(_('Work from %s to %s'), $start, $end)
    ]);
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

    $pricelister = new \FlexiPeeHP\Cenik(\FlexiPeeHP\FlexiBeeRO::code(constant('FLEXIBEE_CENIK')));
    if ($pricelister->lastResponseCode == 404) {
        $pricelister->insertToFlexiBee(['code' => constant('FLEXIBEE_CENIK'), 'nazev' => constant('FLEXIBEE_CENIK'),
            'typZasobyK' => 'typZasoby.sluzba', 'skladove' => false, 'cenaZakl' => 1,
            'cenaZaklBezDph' => 1]);
    }

    foreach (array_keys($projects) as $projectID) {
//        if (array_key_exists('time_entry_activities', $projectInfo)) {
//            $items = $projectInfo['time_entry_activities'];
//        } else {
        $items = $redminer->getProjectTimeEntries($projectID, $start, $end, $userID);


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

