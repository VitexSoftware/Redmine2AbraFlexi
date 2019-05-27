<?php

namespace Redmine2FlexiBee;

require_once '../vendor/autoload.php';

session_start();

\Ease\Shared::instanced()->loadConfig('../config.json', true);

$oPage = new ui\WebPage('Redmine2FlexiBee: Choose redmine projects');

$oPage->addCSS('.row:hover{
    color:red ;
    background-color: yellow;
}');

$redminer = new RedmineRestClient();
$addreser = new \FlexiPeeHP\Adresar();

$deffirma = $oPage->getRequestValue('firma') ? current($oPage->getRequestValue('firma')) : null ;

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

    $projectsForm->addInput(new \Ease\Html\InputNumberTag('userid',null), _('Redimine user Id'));

    $projectsForm->addItem(
        ['<a href="#" class="btn btn-inverse" onClick="$(\'.projectswitch\').bootstrapSwitch(\'toggleState\');">'.str_repeat(new \Ease\TWB\GlyphIcon('refresh'),
                10).'</a> ']);

    foreach ($projects as $projectID => $projectData) {
        if ($projectData['status'] != 1) {
            $redminer->addStatusMessage(sprintf(_('Disabled project %s skipped'),
                    $projectData['name']));
            continue;
        }

        if (array_key_exists('custom_columns', $projectData)) {
            $fbClient = $projectData['custom_columns'];
        } else {
            $fbClient = $deffirma;
            $redminer->addStatusMessage(sprintf(_('there is no custom column "FIRMA" in project %s'),
                    $projectData['name']), 'warning');
        }

        $projectInfo = $redminer->getProjectInfo($projectID);
        $projectRow  = new \Ease\TWB\Row();
        $projectRow->addColumn(2,
            new \Ease\Html\ATag(constant('REDMINE_URL').'projects/'.$projectData['identifier'],
            $projectData['name']));
        $projectRow->addColumn(2,
            new \Ease\ui\TWBSwitch('project['.$projectID.']', null, 'on',
            ['class' => 'projectswitch']));
        $projectRow->addColumn(4, $projectData['description']);

        if (empty($fbClient)) {
            $companyColumn = $projectRow->addColumn(4,
                new \Ease\TWB\LinkButton('', _('no FlexiBee company set'),
                'warning'));

            $companyColumn->addItem(new ui\SearchBox('firma['.$projectID.']',
                $fbClient,
                ['id' => 'project'.$projectID,
                'data-remote-list' => 'firmy.php',
                'data-list-highlight' => 'true',
                'data-list-value-completion' => 'true'
            ]));
        } else {
            $addreser->setDataValue('kod',$fbClient);
            $projectRow->addColumn(4,
                new \Ease\TWB\LinkButton($addreser->getApiURL(),
                $addreser->getRecordCode(),
                empty($addreser->getMyKey()) ? 'warning' : 'success' ));
        }
        $projectsForm->addItem($projectRow);
    }

    $projectsForm->addInput(new \FlexiPeeHP\ui\RecordTypeSelect(
        new \FlexiPeeHP\FlexiBeeRO(null,['evidence' => 'typ-faktury-vydane']), 'kod'), _('Create invoice of type'));

    $projectsForm->addItem(new \Ease\TWB\SubmitButton(sprintf(_('Import to %s'),
            constant('FLEXIBEE_URL').'/c/'.constant('FLEXIBEE_COMPANY')),
        'success'));
}


$oPage->addItem(new \Ease\TWB\Container(new \Ease\TWB\Panel(_('Choose Redmine Projects'),
    'warning', $projectsForm, $oPage->getStatusMessagesAsHtml())));

$oPage->draw();
