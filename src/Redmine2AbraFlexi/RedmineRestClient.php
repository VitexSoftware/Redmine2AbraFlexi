<?php

declare(strict_types=1);

/**
 * This file is part of the RedMine2AbraFlexi package
 *
 * https://github.com/VitexSoftware/Redmine2AbraFlexi/
 *
 * (c) Vítězslav Dvořák <https://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Redmine2AbraFlexi;

/**
 * Description of RedmineRestClient.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class RedmineRestClient extends \AbraFlexi\RO
{
    use \Ease\datescope;
    public \DateTime $redmineSince;
    public \DateTime $redmineUntil;
    public array $defaultUrlParams = ['limit' => 500];

    /**
     * RedMine REST client.
     *
     * @param mixed                 $init
     * @param array<string, string> $options
     */
    public function __construct($init = null, array $options = [])
    {
        parent::__construct($init, $options);
    }

    /**
     * SetUp Object to be ready for connect.
     *
     * @param array<string, string> $options Object Options (company,url,user,password,evidence,
     *                                       prefix,defaultUrlParams,debug)
     */
    public function setUp($options = []): bool
    {
        $this->setupProperty($options, 'url', 'REDMINE_URL');
        $this->setupProperty($options, 'user', 'REDMINE_USERNAME');
        $this->setupProperty($options, 'password', 'REDMINE_PASSWORD');
        $this->setupProperty($options, 'debug');
        $this->updateApiURL();

        return true;
    }

    /**
     * Nastaví Evidenci pro Komunikaci.
     * Set evidence for communication.
     *
     * @param string $evidence evidence pathName to use
     *
     * @return bool evidence switching status
     */
    public function setEvidence($evidence)
    {
        $this->evidence = $evidence;
        $result = true;
        $this->updateApiURL();

        return $result;
    }

    /**
     * Return basic URL for used Evidence.
     *
     * @return string Evidence URL
     */
    public function getEvidenceURL()
    {
        $evidenceUrl = $this->url;
        $evidence = $this->getEvidence();

        if (!empty($evidence)) {
            $evidenceUrl .= '/'.$evidence;
        }

        return $evidenceUrl;
    }

    /**
     * Obtain RedMine Projects listing.
     *
     * @param array<string, mixed> $params conditions
     *
     * @return null|array<int, mixed>
     */
    public function getProjects(array $params = []): ?array
    {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams(
            'projects.json',
            $params,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['projects'], 'id');
        }

        return $response;
    }

    /**
     * Obtain RedMine Users List.
     *
     * @param array<string, mixed> $params conditions
     *
     * @return array<int, mixed>
     */
    public function getUsers(array $params = []): array
    {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams(
            '/shared/users.json',
            $params,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['users'], 'id');
        }

        return $response;
    }

    /**
     * Obtain Project Info.
     *
     * @param array<string, mixed> $params Additional request parameters
     *
     * @return array<string, mixed> Project information
     */
    public function getProjectInfo(int $projectID, array $params = []): array
    {
        return $this->performRequest(\Ease\Functions::addUrlParams(
            'projects/'.$projectID.'.json',
            $params,
        ), 'GET')['project'];
    }

    /**
     * Convert Raw response to Array.
     *
     * @param string $responseRaw the raw response string
     * @param string $format      the format of the response
     *
     * @return array<int|string, mixed> the decoded response as an array
     */
    public function rawResponseToArray(string $responseRaw, string $format): array
    {
        return parent::rawResponseToArray($responseRaw, 'json');
    }

    /**
     * Parse Redmine response.
     *
     * @param mixed $responseDecoded the decoded response array
     * @param mixed $responseCode    the HTTP response code
     *
     * @return mixed the parsed response array
     */
    public function parseResponse($responseDecoded, $responseCode)
    {
        return $responseDecoded;
    }

    /**
     * Obtain time entries for a project within a date range and optionally for a specific user.
     *
     * @param int       $userId the user ID
     * @param \DateTime $start  the start date
     * @param \DateTime $end    the end date
     *
     * @return array<int, array<string, mixed>> time entries with issue names
     */
    public function getUserTimeEntries(int $userId, \DateTime $start, \DateTime $end): array
    {
        $timeEntriesRaw = $this->performRequest(
            'time_entries.json?spent_on='.urlencode('><'.$start->format('Y-m-d').'|'.$end->format('Y-m-d')).'&user_id='.$userId,
            'GET',
        );

        if ($timeEntriesRaw && $this->lastResponseCode === 200) {
            $projects = \Ease\Functions::reindexArrayBy($this->getProjects(['limit' => 100]), 'id');

            foreach ($timeEntriesRaw['time_entries'] as $timeEntryId => $timeEntryData) {
                $timeEntriesRaw['time_entries'][$timeEntryId]['project'] = $projects[$timeEntryData['project']['id']];
            }

            $response = $this->addIssueNames(\Ease\Functions::reindexArrayBy($timeEntriesRaw['time_entries'], 'id'));
        }

        return $timeEntriesRaw ? $response : [];
    }

    /**
     * Add Issue names to time entries.
     *
     * @param array<int, array<string, mixed>> $timeEntries array of time entries indexed by ID
     *
     * @return array<int, array<string, mixed>> array of time entries with issue names indexed by ID
     */
    public function addIssueNames(array $timeEntries): array
    {
        $result = [];
        $issues = [];

        foreach ($timeEntries as $timeEntryID => $timeEntry) {
            if (isset($timeEntry['issue'])) {
                $issues[$timeEntry['issue']['id']] = $timeEntry['issue']['id'];
            }

            $result[$timeEntryID] = [
                'project' => $timeEntry['project']['name'],
                'project_slug' => $timeEntry['project']['identifier'],
                'hours' => $timeEntry['hours'],
                'issue' => \array_key_exists('issue', $timeEntry) ? $timeEntry['issue']['id'] : 0,
                'comments' => \array_key_exists('comments', $timeEntry) ? $timeEntry['comments'] : '',
            ];
        }

        if (\count($issues)) {
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
     * Obtain Issue name by IssueID.
     *
     * @param array<int, int> $issuesID array of issue IDs
     *
     * @return array<int, string> array of issue IDs mapped to their subject names
     */
    public function getNameForIssues(array $issuesID): array
    {
        $result = [];
        $response = $this->performRequest('issues.json?status_id=*&issue_id='.implode(
            ',',
            $issuesID,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['issues'], 'id');
        }

        foreach ($response as $issueID => $responseData) {
            $result[$issueID] = $responseData['subject'];
        }

        return $result;
    }

    /**
     * Get Issued.
     *
     * @param array<string, mixed> $conditions conditions for filtering issues
     *
     * @return null|array<int|string, string> returns an array of issue subjects indexed by issue ID, or null on failure
     */
    public function getIssues(array $conditions): ?array
    {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams(
            'issues.json',
            $conditions,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['issues'], 'id');
        }

        if (\is_array($response)) {
            foreach ($response as $issuesID => $responseData) {
                $result[$issuesID] = $responseData['subject'];
            }
        }

        return $result;
    }

    /**
     * Obtain Issue Info.
     *
     * @param int $id of Issue
     *
     * @return array<int, mixed>
     */
    public function getIssueInfo(int $id): ?array
    {
        return $this->getIssues(['issue_id' => $id, 'status_id' => '*']);
    }
}
