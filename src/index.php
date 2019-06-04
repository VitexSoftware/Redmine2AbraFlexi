<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

\Ease\Shared::instanced()->loadConfig('../config.json', true);

new \Ease\Locale('cs_CZ', '../i18n', 'redmine2flexibee');

session_start();

$oPage = new ui\WebPage('Redmine2FlexiBee');

$rdmurl = $oPage->getRequestValue('rdmurl');
$apikey = $oPage->getRequestValue('apikey');

if (defined('REDMINE_URL')) {
    $_SESSION['REDMINE_URL'] = constant('REDMINE_URL');
} else {
    if ($rdmurl) {
        $_SESSION['REDMINE_URL'] = $rdmurl;
    } else {
        if (!isset($_SESSION['REDMINE_URL'])) {
            $_SESSION['REDMINE_URL'] = '';
        }
    }
    define('REDMINE_URL', $_SESSION['REDMINE_URL']);
}

if (defined('REDMINE_USERNAME')) {
    $_SESSION['REDMINE_USERNAME'] = constant('REDMINE_USERNAME');
} else {
    if ($apikey) {
        $_SESSION['REDMINE_USERNAME'] = $apikey;
    } else {
        if (!isset($_SESSION['REDMINE_USERNAME'])) {
            $_SESSION['REDMINE_USERNAME'] = '';
        }
    }
    define('REDMINE_USERNAME', $_SESSION['REDMINE_USERNAME']);
}

$setupForm = new \Ease\TWB\Form('setupForm', 'index.php');

$setupForm->addInput(new \Ease\Html\InputTextTag('rdmurl',
        $_SESSION['REDMINE_URL']), _('Redmine URL'));

$setupForm->addInput(new \Ease\Html\InputTextTag('apikey',
        $_SESSION['REDMINE_USERNAME']), _('Redmine API Key'));


$setupForm->addItem(new \Ease\TWB\SubmitButton(_('Recheck'), 'inverse' ));




$cstmrForm = new \Ease\TWB\Form('cstmr', 'redmineprojects.php');

$cstmrForm->addInput(new ui\SearchBox('firma[0]', null,
        [
        'data-remote-list' => 'firmy.php',
        'data-list-highlight' => 'true',
        'data-list-value-completion' => 'true'
        ]), _('Default Customer'),
    _('COMPANY_CODE'),_('Use chosen company as customer if not overrided'));

$cstmrForm->addItem(new \Ease\TWB\SubmitButton(_('Choose project')));


//$setupForm->addItem(new \Ease\TWB\LinkButton('redmineusers.php',
//    _('Choose workers')));


$oPage->addItem(new \Ease\TWB\Container($setupForm));
$oPage->addItem(new \Ease\TWB\Container($cstmrForm));

$oPage->addItem(new ui\HealthCehck());

$oPage->draw();
