<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Redmine2FlexiBee\ui;
/**
 * Description of HealthCehck
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class HealthCehck extends \Ease\TWB\Well
{

    public function __construct()
    {
        parent::__construct(new \Ease\Html\H4Tag(_('API Health Check')));

        $this->addItem(
            new \Ease\TWB\LinkButton(constant('FLEXIBEE_URL'), _('FlexiBee'),
                $this->checkFlexiBee().' btn-lg'));
        $this->addItem(
            new \Ease\TWB\LinkButton(constant('REDMINE_URL'), _('Redmine'),
                $this->checkRedmine().' btn-lg'));
    }

    /**
     * Check FlexiBeee
     * 
     * @return boolean api availbility status
     */
    public function checkFlexiBee()
    {
        $flexibeer = new \FlexiPeeHP\Adresar();
        $flexibeer->getFirstRecordID();
        return $flexibeer->lastResponseCode == 200 ? 'success' : 'danger';
    }

    /**
     * 
     * @return boolean
     */
    public function checkRedmine()
    {
        $redminer = new \Redmine2FlexiBee\RedmineRestClient();
        $redminer->getProjects();
        return ($redminer->lastResponseCode == 200) ? 'success' : 'danger';
    }
}
