<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

$oPage = new ui\WebPage('Redmine2FlexiBee: Obtain Time Entries');

$projects = $oPage->getRequestValue('project');

if (empty($projects)) {
    $oPage->addStatusMessage(_('Please Select some projects to import'));
    $oPage->redirect('redmineprojects.php');
} else {
    \Ease\Shared::instanced()->loadConfig('../config.json');

    $invoicer = new FakturaVydana();
    $redminer = new RedmineRestClient();
    foreach (array_keys($projects) as $projectID) {
        $invoicer->takeItemsFromArray($redminer->getTimeEntries($projectID));
    }

    $created = $invoicer->refresh();

    $invoiceTabs = new \Ease\TWB\Tabs('Invoices');

    $invoiceTabs->addTab(_('Html'),
        new \FlexiPeeHP\Bricks\EmbedResponsiveHTML($invoicer));
    $invoiceTabs->addTab(_('PDF'),
        new \FlexiPeeHP\Bricks\EmbedResponsivePDF($invoicer));

    $oPage->addItem(new \Ease\TWB\Panel('Doklad '.$invoicer->getDataValue('kod').' '.($created
                    ? 'byl' : 'nebyl').' vystaven',
            $created ? 'success' : 'danger', $invoiceTabs,
            $oPage->getStatusMessagesAsHtml()));
    $oPage->draw();
}

