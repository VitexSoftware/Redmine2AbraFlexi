<?php
/**
 * Vyrobí Fakturu z CSV exportu Redmine Tiesheet Pluginu
 */

namespace Redmine2AbraFlexi;

require_once '../vendor/autoload.php';

\Ease\Shared::instanced()->loadConfig('../config.json');

$oPage = new ui\WebPage('Redmine2AbraFlexi');

$invoicer = new FakturaVydana();
$invoicer->takeItemsFromCSV(new CSVReader('../timesheet.csv'));


$created = $invoicer->refresh();

$invoiceTabs = new \Ease\TWB\Tabs('Invoices');

$invoiceTabs->addTab(_('Html'),
    new \AbraFlexi\Bricks\EmbedResponsiveHTML($invoicer));
$invoiceTabs->addTab(_('PDF'),
    new \AbraFlexi\Bricks\EmbedResponsivePDF($invoicer));

$oPage->addItem(new \Ease\TWB\Panel('Doklad '.$invoicer->getDataValue('kod').' '.($created
                ? 'byl' : 'nebyl').' vystaven', $created ? 'success' : 'danger',
        $invoiceTabs, $oPage->getStatusMessagesAsHtml()));

$oPage->draw();
