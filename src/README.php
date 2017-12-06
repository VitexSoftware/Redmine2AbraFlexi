<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

\Ease\Shared::instanced()->loadConfig('../config.json');

$oPage = new ui\WebPage('Redmine2FlexiBee: Choose redmine projects');

$redminer = new RedmineRestClient();

$projects = $redminer->getProjects();

if (empty($projects)) {
    $projectsForm = new \Ease\TWB\Label('warning', _('No projects found'));
} else {
    $projectsForm = new \Ease\TWB\Form('Projects', 'redminetimeentries.php');
    foreach ($projects as $projectID => $projectData) {
        $projectRow = new \Ease\TWB\Row();
        $projectRow->addColumn(2, $projectData['name']);
        $projectRow->addColumn(2,
            new \Ease\ui\TWBSwitch('project['.$projectID.']'));
        $projectsForm->addItem($projectRow);
    }
}

$projectsForm->addItem(new \Ease\TWB\SubmitButton(_('Import'), 'success'));

$oPage->addItem(new \Ease\TWB\Panel(_('Choose Redmine Projects'), 'warning',
        $projectsForm, $oPage->getStatusMessagesAsHtml()));

$oPage->draw();
