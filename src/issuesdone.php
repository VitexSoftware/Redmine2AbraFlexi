<?php

namespace Redmine2AbraFlexi;

require_once '../vendor/autoload.php';
new \Ease\Locale('cs_CZ', '../i18n', 'redmine2abraflexi');
session_start();

\Ease\Shared::instanced()->loadConfig('../config.json', true);

$oPage = new ui\WebPage('Redmine2AbraFlexi: Issues ready for invoicing');

$oPage->addCSS('.row:hover{
    color:red ;
    background-color: yellow;
}');

$redminer = new RedmineRestClient();
$addreser = new \AbraFlexi\Adresar();

//$projectInfo = $redminer->getProjectInfo(32, //Outsourcing
//    ['include' => 'time_entry_activities']); //since redmine 3.4.0
//if (array_key_exists('custom_fields', $projectInfo)) {
//    foreach ($projectInfo['custom_fields'] as $customFieldInfo) {
//        if ($customFieldInfo['name'] == 'AbraFlexi Firma') {
//            $invoicer->setDataValue('firma',
//                \AbraFlexi\RO::code($customFieldInfo['value']));
//        }
//    }
//}

$forInvoicing = $redminer->getIssues(['status_id' => 11]);

$issuesTable = new \Ease\Html\TableTag(null, ['class' => 'table']);
$hoursTotal = 0.0;
foreach ($forInvoicing as $issueId => $issueName) {
    
    $issueHours = 0.0;
    
    $timeEntries = $redminer->getTimeEntries(['issue_id'=>$issueId]);
    
    foreach ($timeEntries as $timeEntry){
        if(array_key_exists('hours',$timeEntry)){
            $issueHours += $timeEntry['hours'];
        }
    }
    
    $issuesTable->addRowColumns([$issueId, $issueName,$issueHours.' h']);
    $hoursTotal += $issueHours;
}

$issuesTable->addRowFooterColumns([count($forInvoicing).'x', ($hoursTotal * 500) . ' Kč' ,$hoursTotal.' h' ]);

$oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel(_('Issues ready for Invoicing'),
            'warning', $issuesTable, $oPage->getStatusMessagesAsHtml())));

$oPage->draw();
