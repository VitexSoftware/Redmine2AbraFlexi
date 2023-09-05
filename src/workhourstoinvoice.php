#!/usr/bin/php -f
<?php

namespace Redmine2AbraFlexi;

/**
 * Redmine2AbraFlexi - Generate AbraFlexi invoice from Redmine's workhours
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2023 Vitex Software
 */
define('EASE_APPNAME', 'RedmineWorkHours2Invoice');
require_once '../vendor/autoload.php';
\Ease\Shared::init([
    'ABRAFLEXI_URL',
    'ABRAFLEXI_LOGIN',
    'ABRAFLEXI_PASSWORD',
    'ABRAFLEXI_COMPANY',
    'REDMINE_URL',
    'REDMINE_USERNAME',
    'ABRAFLEXI_CUSTOMER',
    'REDMINE_SCOPE',
    'REDMINE_WORKER_MAIL'
        ], isset($argv[1]) ? $argv[1] : '../.env');
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'redmine2abraflexi');
$redminer = new RedmineRestClient();
if (\Ease\Functions::cfg('APP_DEBUG') == 'True') {
    $redminer->logBanner(\Ease\Shared::appName() . ' v' . \Ease\Shared::appVersion());
}


$workerID = null;
foreach ($redminer->getUsers() as $user) {
    if (array_key_exists('mail', $user) && $user['mail'] == \Ease\Functions::cfg('REDMINE_WORKER_MAIL')) {
        $workerID = intval($user['id']);
        break;
    }
}

if (is_null($user)) {
    $redminer->addStatusMessage(sprintf(_('Worker email %s not found in redmine'), \Ease\Functions::cfg('REDMINE_WORKER_MAIL')), 'error');
    exit(1);
}


$addreser = new \AbraFlexi\Adresar();
$redminer->scopeToInterval(\Ease\Functions::cfg('REDMINE_SCOPE'));
$projects = $redminer->getProjects(['limit' => 100]); //since redmine 3.4.0


if (empty($projects)) {
    $redminer->addStatusMessage(_('No projects found'), 'error');
} else {

    $invoicer = new FakturaVydana([
        'typDokl' => \AbraFlexi\RO::code(\Ease\Functions::cfg('ABRAFLEXI_TYP_FAKTURY', 'FAKTURA')),
        'firma' => \AbraFlexi\RO::code(\Ease\Functions::cfg('ABRAFLEXI_CUSTOMER')),
        'popis' => sprintf(_('Work from %s to %s'), $redminer->since->format('Y-m-d'), $redminer->until->format('Y-m-d'))
    ]);
    $pricelister = new \AbraFlexi\Cenik(\AbraFlexi\RO::code(\Ease\Functions::cfg('ABRAFLEXI_CENIK')));
    foreach (array_keys($projects) as $projectID) {
        $items = $redminer->getProjectTimeEntries($projectID, $redminer->since->format('Y-m-d'), $redminer->until->format('Y-m-d'), $workerID);
        if (!empty($items)) {
            $invoicer->takeItemsFromArray($items);
        }
    }

    if(\Ease\Functions::cfg('ABRAFLEXI_SEND','False') == 'True'){
        $invoicer->setDataValue('stavMailK', 'stavMail.odeslat');
    }
    
    $created = $invoicer->sync();
    $invoicer->addStatusMessage($invoicer->getRecordCode() . ' ' . $invoicer->getDataValue('sumCelkem') . ' ' . \AbraFlexi\RO::uncode($invoicer->getDataValue('mena')), $created ? 'success' : 'error');
}
