<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Redmine2FlexiBee;

/**
 * Description of RedmineRestClient
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class RedmineRestClient extends \FlexiPeeHP\FlexiBeeRO
{

    public function __construct($init = null, $options = array())
    {
        parent::__construct($init, $options);
    }

    /**
     * SetUp Object to be ready for connect
     *
     * @param array $options Object Options (company,url,user,password,evidence,
     *                                       prefix,defaultUrlParams,debug)
     */
    public function setUp($options = [])
    {
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
     * @return boolean evidence switching status
     */
    public function setEvidence($evidence)
    {
        $this->evidence = $evidence;
        $result         = true;
        $this->updateApiURL();
        return $result;
    }

    /**
     * Return basic URL for used Evidence
     *
     * @return string Evidence URL
     */
    public function getEvidenceURL()
    {
        $evidenceUrl = $this->url;
        $evidence    = $this->getEvidence();
        if (!empty($evidence)) {
            $evidenceUrl .= '/'.$evidence;
        }
        return $evidenceUrl;
    }

    public function getProjects()
    {
        $result   = null;
        $response = $this->performRequest('projects.json', 'GET');
        if ($this->lastResponseCode == 200) {
            $response = self::reindexArrayBy($response['projects'], 'id');
        }
        return $response;
    }

    public function rawResponseToArray($responseRaw, $format)
    {
        return parent::rawResponseToArray($responseRaw, 'json');
    }

    public function parseResponse($responseDecoded, $responseCode)
    {
        return $responseDecoded;
    }

    public function getTimeEntries($projectID)
    {
        $result   = null;
        $response = $this->performRequest('time_entries.json?project_id='.$projectID,
            'GET');
        if ($this->lastResponseCode == 200) {
            $response = $this->addIssueNames(self::reindexArrayBy($response['time_entries'],
                    'id'));
        }
        return $response;
    }

    public function addIssueNames($timeEntries)
    {
        $result = [];
        $issues = [];
        foreach ($timeEntries as $timeEntryID => $timeEntry) {
            if (isset($timeEntry['issue'])) {
                $issues[$timeEntry['issue']['id']] = $timeEntry['issue']['id'];
            }
            $result[$timeEntryID] = [
                'project' => $timeEntry['project']['name'],
                'hours' => $timeEntry['hours'],
                'issue' => $timeEntry['issue']['id'],
                'comments' => $timeEntry['comments']
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

    public function getNameForIssues($issuesID)
    {
        $result   = null;
        $response = $this->performRequest('issues.json?issue_id='.implode(',',
                $issuesID), 'GET');
        if ($this->lastResponseCode == 200) {
            $response = self::reindexArrayBy($response['issues'], 'id');
        }
        foreach ($response as $issuesID => $responseData) {
            $result[$issuesID] = $responseData['subject'];
        }
        return $result;
    }
}