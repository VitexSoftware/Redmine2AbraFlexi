<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

\Ease\Shared::instanced()->loadConfig('../config.json');

$oPage = new ui\WebPage('Redmine2FlexiBee: Choose redmine projects');
$oPage->addCSS('.row:hover{
    color:red ;
    background-color: yellow;
}');

$redminer = new RedmineRestClient();

$projects = $redminer->getProjects(); //since redmine 3.4.0

if (empty($projects)) {
    $projectsForm = new \Ease\TWB\Label('warning', _('No projects found'));
} else {
    $projectsForm = new \Ease\TWB\Form('Projects', 'redminetimeentries.php');

    $projectsForm->addInput(new \Ease\Html\InputDateTag('startdate',
            new \DateTime("first day of last month")), _('From'));
    $projectsForm->addInput(new \Ease\Html\InputDateTag('enddate',
            new \DateTime("last day of last month")), _('To'));

    foreach ($projects as $projectID => $projectData) {

        if ($projectData['status'] != 1) {
            $redminer->addStatusMessage(sprintf(_('Disabled project %s skipped'), $projectData['name'] ));
            continue;
        }
        $projectInfo = $redminer->getProjectInfo($projectID);


        $projectRow = new \Ease\TWB\Row();
        $projectRow->addColumn(2,
            new \Ease\Html\ATag(constant('REDMINE_URL').'projects/'.$projectData['identifier'],
                $projectData['name']));
        $projectRow->addColumn(2,
            new \Ease\ui\TWBSwitch('project['.$projectID.']'));
        $projectRow->addColumn(4, $projectData['description']);
        $projectsForm->addItem($projectRow);
    }
}

$projectsForm->addItem(new \Ease\TWB\SubmitButton( sprintf( _('Import to %s'),            constant('FLEXIBEE_URL').'/c/'.constant('FLEXIBEE_COMPANY') ), 'success'));

$oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel(_('Choose Redmine Projects'),
            'warning', $projectsForm, $oPage->getStatusMessagesAsHtml())));

$oPage->draw();
