#!/usr/bin/php -f
<?php

declare(strict_types=1);

/**
 * This file is part of the xls2abralexi package
 *
 * https://multiflexi.eu/
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Redmine2AbraFlexi;

/**
 * Redmine2AbraFlexi - Generate AbraFlexi invoice from Redmine's workhours.
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2023 Vitex Software
 */
\define('EASE_APPNAME', 'RedmineWorkHours2Invoice');

require_once '../vendor/autoload.php';
\Ease\Shared::init([
    'ABRAFLEXI_URL',
    'ABRAFLEXI_LOGIN',
    'ABRAFLEXI_PASSWORD',
    'ABRAFLEXI_COMPANY',
    'REDMINE_URL',
    'REDMINE_USERNAME',
    'ABRAFLEXI_CUSTOMER',
    'ABRAFLEXI_CENIK',
    'REDMINE_SCOPE',
    'REDMINE_WORKER_MAIL',
], $argv[1] ?? '../.env');
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'redmine2abraflexi');
$redminer = new RedmineRestClient();

if (strtolower(\Ease\Functions::cfg('APP_DEBUG', 'false')) === 'true') {
    $redminer->logBanner(\Ease\Shared::appName().' v'.\Ease\Shared::appVersion());
}

$workerID = null;

foreach ($redminer->getUsers() as $user) {
    if ($user === 404) {
        $redminer->addStatusMessage(_('Is API plugin availble ?'), 'error');

        exit(1);
    }

    if (\array_key_exists('mail', $user) && $user['mail'] === \Ease\Shared::cfg('REDMINE_WORKER_MAIL')) {
        $workerID = (int) $user['id'];

        break;
    }
}

if (null === $workerID) {
    $redminer->addStatusMessage(sprintf(_('Worker email %s not found in redmine'), \Ease\Functions::cfg('REDMINE_WORKER_MAIL')), 'error');

    exit(1);
}

$addreser = new \AbraFlexi\Adresar();
$redminer->scopeToInterval(\Ease\Functions::cfg('REDMINE_SCOPE'));
$projects = $redminer->getProjects(['limit' => 100]); // since redmine 3.4.0

if (empty($projects)) {
    $redminer->addStatusMessage(_('No projects found'), 'error');
} else {
    $invoicer = new FakturaVydana([
        'typDokl' => \AbraFlexi\RO::code(\Ease\Functions::cfg('ABRAFLEXI_TYP_FAKTURY', 'FAKTURA')),
        'firma' => \AbraFlexi\RO::code(\Ease\Functions::cfg('ABRAFLEXI_CUSTOMER')),
        'popis' => sprintf(_('Work from %s to %s'), $redminer->since->format('Y-m-d'), $redminer->until->format('Y-m-d')),
    ]);
    $pricelister = new \AbraFlexi\Cenik(\AbraFlexi\RO::code(\Ease\Functions::cfg('ABRAFLEXI_CENIK')));

    foreach (array_keys($projects) as $projectID) {
        if (\strlen(\Ease\Shared::cfg('REDMINE_PROJECT', '')) && $projects[$projectID]['identifier'] !== \Ease\Shared::cfg('REDMINE_PROJECT')) {
            continue;
        }

        $items = $redminer->getProjectTimeEntries($projectID, $redminer->since->format('Y-m-d'), $redminer->until->format('Y-m-d'), $workerID);

        if (!empty($items)) {
            $invoicer->takeItemsFromArray($items);
        }
    }

    if (strtolower(\Ease\Functions::cfg('ABRAFLEXI_SEND', 'false')) === 'true') {
        $invoicer->setDataValue('stavMailK', 'stavMail.odeslat');
    }

    $created = $invoicer->sync();
    $invoicer->addStatusMessage($invoicer->getRecordCode().' '.$invoicer->getDataValue('sumCelkem').' '.\AbraFlexi\RO::uncode((string) $invoicer->getDataValue('mena')), $created ? 'success' : 'error');
}
