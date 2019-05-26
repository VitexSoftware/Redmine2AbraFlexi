<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

session_start();

\Ease\Shared::instanced()->loadConfig('../config.json', true);

$oPage = new ui\WebPage('Redmine2FlexiBee: Choose redmine users');

$oPage->addCSS('.row:hover{
    color:red ;
    background-color: yellow;
}');

$redminer = new RedmineRestClient();

$users = $redminer->getUsers();

if (empty($users)) {
    $usersForm = new \Ease\Html\ATag('index.php',
        new \Ease\TWB\Label('warning', _('No users found')));
} else {
    
}


$oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel(_('Choose Redmine Users'),
    'warning', $usersForm, $oPage->getStatusMessagesAsHtml())));

$oPage->draw();
