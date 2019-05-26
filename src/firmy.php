<?php

namespace Redmine2FlexiBee;

/**
 * System.Spoje.Net - Zdroj dat našeptávače kódů produktů
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */
require_once '../vendor/autoload.php';

\Ease\Shared::instanced()->loadConfig('../config.json',TRUE);

$oPage = new \Ease\WebPage();

header('Content-type: application/json');
$fetcher = new \FlexiPeeHP\Adresar();

$kod = current($oPage->getRequestValue('firma'));

if (is_null($kod)) {
    $items = [];
} else {
    $itemsRaw = $fetcher->getColumnsFromFlexiBee(['kod', 'nazev'],
        ['detail' => 'custom:kod,nazev', "( kod like similar '".$kod."') or ( nazev like similar '".$kod."' )"]);

    foreach ($itemsRaw as $item) {
        $items[] = ['value' => $item['kod'], 'label' => $item['kod'].': '.$item['nazev']];
    }
}
echo json_encode($items);
