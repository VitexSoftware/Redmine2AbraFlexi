<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

\Ease\Shared::instanced()->loadConfig('../config.json');

$oPage = new ui\WebPage('Redmine2FlexiBee');

$invoicer = new FakturaVydana();
$invoicer->takeItemsFromCSV(new CSVReader('../timesheet.csv'));


$created = $invoicer->refresh();



$invoiceTabs = new \Ease\TWB\Tabs('Invoices');

$invoiceTabs->addTab(_('Html'),
    new \FlexiPeeHP\Bricks\EmbedResponsiveHTML($invoicer));
$invoiceTabs->addTab(_('PDF'),
    new \FlexiPeeHP\Bricks\EmbedResponsivePDF($invoicer));

$oPage->addItem(new \Ease\TWB\Panel('Doklad '.$invoicer->getDataValue('kod').' '.($created
                ? 'byl' : 'nebyl').' vystaven', $created ? 'success' : 'danger',
        $invoiceTabs, $oPage->getStatusMessagesAsHtml()));

$oPage->draw();
