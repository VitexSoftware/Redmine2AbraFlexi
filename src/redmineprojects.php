<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

session_start();

\Ease\Shared::instanced()->loadConfig('../config.json', true);

$oPage = new ui\WebPage('Redmine2FlexiBee: Choose redmine projects');

define('REDMINE_URL', $_SESSION['REDMINE_URL']);
define('REDMINE_USERNAME', $_SESSION['REDMINE_USERNAME']);

$oPage->addCSS('.row:hover{
    color:red ;
    background-color: yellow;
}');

$redminer = new RedmineRestClient();
$addreser = new \FlexiPeeHP\Adresar();

$projects = $redminer->getProjects(); //since redmine 3.4.0

if (empty($projects)) {
    $projectsForm = new \Ease\Html\ATag('index.php',
        new \Ease\TWB\Label('warning', _('No projects found')));
} else {
    $projectsForm = new \Ease\TWB\Form('Projects', 'redminetimeentries.php');

    $projectsForm->addInput(new \Ease\Html\InputDateTag('startdate',
        new \DateTime("first day of last month")), _('From'));
    $projectsForm->addInput(new \Ease\Html\InputDateTag('enddate',
        new \DateTime("last day of last month")), _('To'));

    foreach ($projects as $projectID => $projectData) {
        if ($projectData['status'] != 1) {
            $redminer->addStatusMessage(sprintf(_('Disabled project %s skipped'),
                    $projectData['name']));
            continue;
        }

        $fbClient = $projectData['custom_columns'];

        $projectInfo = $redminer->getProjectInfo($projectID);
        $projectRow  = new \Ease\TWB\Row();
        $projectRow->addColumn(2,
            new \Ease\Html\ATag(constant('REDMINE_URL').'projects/'.$projectData['identifier'],
            $projectData['name']));
        $projectRow->addColumn(2,
            new \Ease\ui\TWBSwitch('project['.$projectID.']'));
        $projectRow->addColumn(4, $projectData['description']);

        if (empty($fbClient)) {
            $projectRow->addColumn(4,
                new \Ease\TWB\LinkButton('', _('no FlexiBee company set'),
                'warning'));
        } else {
            $projectRow->addColumn(4,
                new \Ease\TWB\LinkButton($addreser->getApiURL(),
                $addreser->getRecordCode(),
                empty($addreser->getMyKey()) ? 'warning' : 'success' ));
        }
        $projectsForm->addItem($projectRow);
    }

    $projectsForm->addInput(new \FlexiPeeHP\ui\RecordTypeSelect(
        new \FlexiPeeHP\FlexiBeeRO(), 'kod',
        ['evidence' => 'typ-faktury-vydane']
        ), _('Create invoice of type'));

    $projectsForm->addItem(new \Ease\TWB\SubmitButton(sprintf(_('Import to %s'),
            constant('FLEXIBEE_URL').'/c/'.constant('FLEXIBEE_COMPANY')),
        'success'));
}


$oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel(_('Choose Redmine Projects'),
    'warning', $projectsForm, $oPage->getStatusMessagesAsHtml())));

$oPage->draw();
