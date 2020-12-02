<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Redmine2AbraFlexi;

/**
 * Description of RedmineUserSelect
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class RedmineUserSelect extends \Ease\Html\SelectTag
{
    public function __construct($name, $defaultValue = null,
                                $itemsIDs = false, $properties = array())
    {
        $ruserer = new RedmineRestClient();
        $users = [''=>_('All Users')];
        foreach ($ruserer->getUsers() as $userId => $userInfo){
            $users[$userId] = $userInfo['lastname'].' '.$userInfo['firstname'];
        }
        asort($users);
        parent::__construct($name, $users , $defaultValue, null, $properties);
    }
}
