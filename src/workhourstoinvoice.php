#!/usr/bin/php -f
<?php

declare(strict_types=1);

/**
 * This file is part of the RedMine2AbraFlexi package
 *
 * https://github.com/VitexSoftware/Redmine2AbraFlexi/
 *
 * (c) Vítězslav Dvořák <https://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Redmine2AbraFlexi;

use AbraFlexi\Adresar;
use AbraFlexi\Cenik;
use AbraFlexi\RO;
use Ease\Locale;
use Ease\Shared;

/**
 * Redmine2AbraFlexi - Generate AbraFlexi invoice from Redmine's workhours.
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2023-2025 Vitex Software
 */
\define('EASE_APPNAME', 'RedmineWorkHours2Invoice');

require_once '../vendor/autoload.php';

/**
 * Get today's Statements list.
 */
$options = getopt('o::e::', ['output::environment::']);

Shared::init(
    [
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
    ],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout');
$localer = new Locale('cs_CZ', '../i18n', 'redmine2abraflexi');
$redminer = new RedmineRestClient();

if (strtolower(Shared::cfg('APP_DEBUG', 'false')) === 'true') {
    $redminer->logBanner(Shared::appName().' v'.Shared::appVersion());
}

$report = [];
$exitcode = 0;
$workerID = null;

foreach ($redminer->getUsers() as $user) {
    if ($user === 404) {
        $redminer->addStatusMessage(_('Is API plugin availble ?'), 'error');

        exit(1);
    }

    if (\array_key_exists('mail', $user) && $user['mail'] === Shared::cfg('REDMINE_WORKER_MAIL')) {
        $workerID = (int) $user['id'];

        break;
    }
}

if (null === $workerID) {
    $redminer->addStatusMessage(sprintf(_('Worker email %s not found in redmine'), Shared::cfg('REDMINE_WORKER_MAIL')), 'error');

    exit(1);
}

$addreser = new Adresar();
$redminer->scopeToInterval(Shared::cfg('REDMINE_SCOPE'));
$projects = $redminer->getProjects(['limit' => 1000]); // since redmine 3.4.0

if (empty($projects)) {
    $report['message'] = _('No projects found');
    $redminer->addStatusMessage($report['message'], 'error');
} else {
    $invoicer = new FakturaVydana([
        'typDokl' => \AbraFlexi\Functions::code(Shared::cfg('ABRAFLEXI_TYP_FAKTURY', 'FAKTURA')),
        'firma' => \AbraFlexi\Functions::code(Shared::cfg('ABRAFLEXI_CUSTOMER')),
        'popis' => sprintf(_('Work from %s to %s'), $redminer->since->format('Y-m-d'), $redminer->until->format('Y-m-d')),
    ]);
    $pricelister = new Cenik(\AbraFlexi\Functions::code(Shared::cfg('ABRAFLEXI_CENIK')));

    foreach (array_keys($projects) as $projectID) {
        if (\strlen(Shared::cfg('REDMINE_PROJECT', '')) && $projects[$projectID]['identifier'] !== Shared::cfg('REDMINE_PROJECT')) {
            continue;
        }

        if (strstr(Shared::cfg('REDMINE_SKIPLIST', ''), $projects[$projectID]['identifier'])) {
            $redminer->addStatusMessage(sprintf(_('Skipping project in REDMINE_SKIPLIST: %s'), $projects[$projectID]['identifier']));

            continue;
        }

        $items = $redminer->getProjectTimeEntries($projectID, $redminer->since, $redminer->until, $workerID);
        $report[$projectID] = $items;

        if (empty($items) === false) {
            $invoicer->takeItemsFromArray($items);
        }
    }

    if (strtolower(Shared::cfg('ABRAFLEXI_SEND', 'false')) === 'true') {
        $invoicer->setDataValue('stavMailK', 'stavMail.odeslat');
    }

    if ($invoicer->getSubItems()) {
        $created = $invoicer->sync();
        $report['message'] = $invoicer->getRecordCode().' '.$invoicer->getDataValue('sumCelkem').' '.RO::uncode((string) $invoicer->getDataValue('mena'));
        $invoicer->addStatusMessage($report['message'], $created ? 'success' : 'error');
    } else {
        $report['message'] = _('Invoice Empty');
        $invoicer->addStatusMessage($report['message'], 'success');
    }
}

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$redminer->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
