<?php

namespace Redmine2AbraFlexi;

require_once '../vendor/autoload.php';
new \Ease\Locale('cs_CZ', '../i18n', 'redmine2abraflexi');
session_start();

$oPage  = new ui\WebPage('Redmine2AbraFlexi: Obtain Time Entries');
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
        "timesheet[period]" => "last_month",
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
        'typDokl' => \AbraFlexi\RO::code($typFak),
        'firma' => \AbraFlexi\RO::code($firma),
        'popis' => sprintf(_('Work from %s to %s'), $start, $end)
    ]);
    $redminer = new RedmineRestClient();
    if (count($projects) == 1) {
        $projectInfo = $redminer->getProjectInfo(key($projects),
            ['include' => 'time_entry_activities']); //since redmine 3.4.0
        if (array_key_exists('custom_fields', $projectInfo)) {
            foreach ($projectInfo['custom_fields'] as $customFieldInfo) {
                if ($customFieldInfo['name'] == 'AbraFlexi Firma') {
                    $invoicer->setDataValue('firma',
                        \AbraFlexi\RO::code($customFieldInfo['value']));
                }
            }
        }
    }

    $pricelister = new \AbraFlexi\Cenik(\AbraFlexi\RO::code(constant('ABRAFLEXI_CENIK')));
    if ($pricelister->lastResponseCode == 404) {
        $pricelister->insertToAbraFlexi(['code' => constant('ABRAFLEXI_CENIK'), 'nazev' => constant('ABRAFLEXI_CENIK'),
            'typZasobyK' => 'typZasoby.sluzba', 'skladove' => false, 'cenaZakl' => 1,
            'cenaZaklBezDph' => 1]);
    }

    foreach (array_keys($projects) as $projectID) {
//        if (array_key_exists('time_entry_activities', $projectInfo)) {
//            $items = $projectInfo['time_entry_activities'];
//        } else {
        $items = $redminer->getProjectTimeEntries($projectID, $start, $end,
            $userID);


//        }

        if (!empty($items)) {


            $invoicer->takeItemsFromArray($items);
        }
    }

    $created = $invoicer->sync();

    $invoiceTabs = new \Ease\TWB\Tabs('Invoices');

    $invoiceTabs->addTab(_('Html'),
        new \AbraFlexi\ui\EmbedResponsiveHTML($invoicer));
    $invoiceTabs->addTab(_('PDF'),
        new \AbraFlexi\ui\EmbedResponsivePDF($invoicer));

    $oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel('Doklad '.new \Ease\Html\ATag($invoicer->getApiUrl(),
                    $invoicer->getDataValue('kod')).' '.($created ? 'byl' : 'nebyl').' vystaven',
                $created ? 'success' : 'danger', $invoiceTabs,
                $oPage->getStatusMessagesAsHtml())));
    $oPage->draw();
}

