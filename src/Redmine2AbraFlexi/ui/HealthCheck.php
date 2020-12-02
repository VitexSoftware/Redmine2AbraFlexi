<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Redmine2AbraFlexi\ui;
/**
 * Description of HealthCehck
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class HealthCheck extends \Ease\TWB\Well
{

    public function __construct()
    {
        parent::__construct(new \Ease\Html\H4Tag(_('API Health Check')));

        $this->addItem(
            new \Ease\TWB\LinkButton(constant('ABRAFLEXI_URL'), _('AbraFlexi'),
                $this->checkAbraFlexi().' btn-lg'));
        $this->addItem(
            new \Ease\TWB\LinkButton(constant('REDMINE_URL'), _('Redmine'),
                $this->checkRedmine().' btn-lg'));
    }

    /**
     * Check AbraFlexie
     * 
     * @return boolean api availbility status
     */
    public function checkAbraFlexi()
    {
        $abraflexir = new \AbraFlexi\Adresar();
        $abraflexir->getFirstRecordID();
        return $abraflexir->lastResponseCode == 200 ? 'success' : 'danger';
    }

    /**
     * 
     * @return boolean
     */
    public function checkRedmine()
    {
        $redminer = new \Redmine2AbraFlexi\RedmineRestClient();
        $redminer->getProjects();
        return ($redminer->lastResponseCode == 200) ? 'success' : 'danger';
    }
}
