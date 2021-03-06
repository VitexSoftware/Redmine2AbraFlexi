<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Redmine2AbraFlexi;

/**
 * Description of RedmineRestClient
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class RedmineRestClient extends \AbraFlexi\RO {

    /**
     * 
     * @param type $init
     * @param type $options
     */
    public function __construct($init = null, $options = array()) {
        parent::__construct($init, $options);
    }

    /**
     * SetUp Object to be ready for connect
     *
     * @param array $options Object Options (company,url,user,password,evidence,
     *                                       prefix,defaultUrlParams,debug)
     */
    public function setUp($options = []) {
        $this->setupProperty($options, 'url', 'REDMINE_URL');
        $this->setupProperty($options, 'user', 'REDMINE_USERNAME');
        $this->setupProperty($options, 'password', 'REDMINE_PASSWORD');
        $this->setupProperty($options, 'debug');
        $this->updateApiURL();
    }

    /**
     * Nastaví Evidenci pro Komunikaci.
     * Set evidence for communication
     *
     * @param string $evidence evidence pathName to use
     * 
     * @return boolean evidence switching status
     */
    public function setEvidence($evidence) {
        $this->evidence = $evidence;
        $result = true;
        $this->updateApiURL();
        return $result;
    }

    /**
     * Return basic URL for used Evidence
     *
     * @return string Evidence URL
     */
    public function getEvidenceURL() {
        $evidenceUrl = $this->url;
        $evidence = $this->getEvidence();
        if (!empty($evidence)) {
            $evidenceUrl .= '/' . $evidence;
        }
        return $evidenceUrl;
    }

    /**
     * Obtaing Redmine Projects listing
     * 
     * @param array $params conditions
     * 
     * @return array
     */
    public function getProjects($params = []) {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams('projects.json',
                        $params), 'GET');
        if ($this->lastResponseCode == 200) {
            $response = \Ease\Functions::reindexArrayBy($response['projects'], 'id');
        }
        return $response;
    }

    /**
     * Obtain Redmine  Users List
     * 
     * @param array $params conditions
     * 
     * @return array
     */
    public function getUsers($params = []) {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams('/shared/users.json',
                        $params), 'GET');
        if ($this->lastResponseCode == 200) {
            $response = \Ease\Functions::reindexArrayBy($response['users'], 'id');
        }
        return $response;
    }

    /**
     * Obtain Project Info
     * 
     * @param int    $projectID
     * @param string $params     
     * 
     * @return array
     */
    public function getProjectInfo($projectID, $params = []) {
        return $this->performRequest(\Ease\Functions::addUrlParams('projects/' . $projectID . '.json',
                                $params), 'GET')['project'];
    }

    /**
     * 
     * @param type $responseRaw
     * @param type $format
     * 
     * @return type
     */
    public function rawResponseToArray($responseRaw, $format) {
        return parent::rawResponseToArray($responseRaw, 'json');
    }

    /**
     * 
     * @param type $responseDecoded
     * @param type $responseCode
     * 
     * @return type
     */
    public function parseResponse($responseDecoded, $responseCode) {
        return $responseDecoded;
    }

    /**
     * 
     * @param int $projectID
     * @param type $start
     * @param type $end
     * 
     * @return array
     */
    public function getProjectTimeEntries($projectID, $start, $end,
            $userId = null) {
        $result = null;
        $response = $this->performRequest('time_entries.json?project_id=' . $projectID . '&spent_on=' . urlencode('><' . $start . '|' . $end) . '&user_id=' . $userId,
                'GET');
        if ($this->lastResponseCode == 200) {
            $response = $this->addIssueNames(\Ease\Functions::reindexArrayBy($response['time_entries'],
                            'id'));
        }
        return $response;
    }

    /**
     * 
     * @param array $conditions
     * 
     * @return array
     */
    public function getTimeEntries(array $conditions) {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams('time_entries.json',
                        $conditions), 'GET');

        if ($this->lastResponseCode == 200) {
            $response = self::reindexArrayBy($response['time_entries'], 'id');
        }
        return $response;
    }

    /**
     * 
     * @param type $timeEntries
     * 
     * @return type
     */
    public function addIssueNames($timeEntries) {
        $result = [];
        $issues = [];
        foreach ($timeEntries as $timeEntryID => $timeEntry) {
            if (isset($timeEntry['issue'])) {
                $issues[$timeEntry['issue']['id']] = $timeEntry['issue']['id'];
            }
            $result[$timeEntryID] = [
                'project' => $timeEntry['project']['name'],
                'hours' => $timeEntry['hours'],
                'issue' => array_key_exists('issue', $timeEntry) ? $timeEntry['issue']['id'] : 0,
                'comments' => array_key_exists('comments', $timeEntry) ? $timeEntry['comments'] : ''
            ];
        }
        if (count($issues)) {
            $issueInfo = $this->getNameForIssues($issues);
            foreach ($result as $timeEntryID => $timeEntry) {
                if (isset($timeEntry['issue'])) {
                    $issueID = $timeEntry['issue'];
                    if (isset($issueInfo[$issueID])) {
                        $timeEntry['issue'] = $issueInfo[$issueID];
                    } else {
                        $timeEntry['issue'] = $issueID;
                    }
                }
                $result[$timeEntryID] = $timeEntry;
            }
        }
        return $result;
    }

    /**
     * 
     * @param type $issuesID
     * 
     * @return type
     */
    public function getNameForIssues($issuesID) {
        $result = null;
        $response = $this->performRequest('issues.json?status_id=*&issue_id=' . implode(',',
                        $issuesID), 'GET');
        if ($this->lastResponseCode == 200) {
            $response = \Ease\Functions::reindexArrayBy($response['issues'], 'id');
        }
        foreach ($response as $issuesID => $responseData) {
            $result[$issuesID] = $responseData['subject'];
        }
        return $result;
    }

    /**
     * Get Issued
     * 
     * @param array $conditions
     * 
     * @return array
     */
    public function getIssues(array $conditions) {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams('issues.json',
                        $conditions), 'GET');
        if ($this->lastResponseCode == 200) {
            $response = \Ease\Functions::reindexArrayBy($response['issues'], 'id');
        }
        foreach ($response as $issuesID => $responseData) {
            $result[$issuesID] = $responseData['subject'];
        }
        return $result;
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public function getIssueInfo($id) {
        return $this->getIssues(['issue_id' => $id, 'status_id' => '*']);
    }

}
