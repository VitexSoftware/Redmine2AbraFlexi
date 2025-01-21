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
    public \DateTime $since;
    public \DateTime $until;

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
     * Obtain Redmine Projects listing.
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
     * @param int $projectID
     */
    public function getProjectInfo($projectID, array $params = []): array
    {
        return $this->performRequest(\Ease\Functions::addUrlParams(
            'projects/'.$projectID.'.json',
            $params,
        ), 'GET')['project'];
    }

    /**
     * Convert Raw response to Array.
     */
    public function rawResponseToArray(string $responseRaw, string $format): array
    {
        return parent::rawResponseToArray($responseRaw, 'json');
    }

    /**
     * Parse Redmine response.
     *
     * @param array $responseDecoded
     * @param int   $responseCode
     */
    public function parseResponse(/* array */ $responseDecoded, /* int */ $responseCode): array
    {
        return $responseDecoded;
    }

    /**
     * Time Entries obtainer.
     *
     * @param string     $start
     * @param string     $end
     * @param null|mixed $userId
     *
     * @return array
     */
    public function getProjectTimeEntries(int $projectID, \DateTime $start, \DateTime $end, $userId = null)
    {
        $result = null;
        $response = $this->performRequest(
            'time_entries.json?project_id='.$projectID.'&spent_on='.urlencode('><'.$start->format('Y-m-d').'|'.$end->format('Y-m-d')).'&user_id='.$userId,
            'GET',
        );

        if ($this->lastResponseCode === 200) {
            $response = $this->addIssueNames(\Ease\Functions::reindexArrayBy(
                $response['time_entries'],
                'id',
            ));
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getTimeEntries(array $conditions): array|bool
    {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams(
            'time_entries.json',
            $conditions,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['time_entries'], 'id');
        }

        return $response;
    }

    /**
     * Add Issue names to time entries.
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
     */
    public function getNameForIssues(array $issuesID): array
    {
        $result = null;
        $response = $this->performRequest('issues.json?status_id=*&issue_id='.implode(
            ',',
            $issuesID,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['issues'], 'id');
        }

        foreach ($response as $issuesID => $responseData) {
            $result[$issuesID] = $responseData['subject'];
        }

        return $result;
    }

    /**
     * Get Issued.
     */
    public function getIssues(array $conditions): null|array
    {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams(
            'issues.json',
            $conditions,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['issues'], 'id');
        }

        foreach ($response as $issuesID => $responseData) {
            $result[$issuesID] = $responseData['subject'];
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
    public function getIssueInfo(int $id): array|null
    {
        return $this->getIssues(['issue_id' => $id, 'status_id' => '*']);
    }

    /**
     * Prepare processing interval.
     *
     * @param string $scope
     *
     * @throws \Ease\Exception
     */
    public function scopeToInterval($scope): void
    {
        switch ($scope) {
            case 'current_month':
                $this->since = new \DateTime('first day of this month');
                $this->until = new \DateTime();

                break;
            case 'last_month':
                $this->since = new \DateTime('first day of last month');
                $this->until = new \DateTime('last day of last month');

                break;
            case 'last_two_months':
                $this->since = (new \DateTime('first day of last month'))->modify('-1 month');
                $this->until = (new \DateTime('last day of last month'));

                break;
            case 'previous_month':
                $this->since = new \DateTime('first day of -2 month');
                $this->until = new \DateTime('last day of -2 month');

                break;
            case 'two_months_ago':
                $this->since = new \DateTime('first day of -3 month');
                $this->until = new \DateTime('last day of -3 month');

                break;
            case 'this_year':
                $this->since = new \DateTime('first day of January '.date('Y'));
                $this->until = new \DateTime('last day of December'.date('Y'));

                break;
            case 'January':  // 1
            case 'February': // 2
            case 'March':    // 3
            case 'April':    // 4
            case 'May':      // 5
            case 'June':     // 6
            case 'July':     // 7
            case 'August':   // 8
            case 'September':// 9
            case 'October':  // 10
            case 'November': // 11
            case 'December': // 12
                $this->since = new \DateTime('first day of '.$scope.' '.date('Y'));
                $this->until = new \DateTime('last day of '.$scope.' '.date('Y'));

                break;

            default:
                if (strstr($scope, '>')) {
                    [$begin, $end] = explode('>', $scope);
                    $this->since = new \DateTime($begin);
                    $this->until = new \DateTime($end);
                } else {
                    if (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $scope)) {
                        $this->since = new \DateTime($scope);
                        $this->until = (new \DateTime($scope))->setTime(23, 59, 59, 999);

                        break;
                    }

                    throw new \Exception('Unknown scope '.$scope);
                }
        }

        $this->since = $this->since->setTime(0, 0);
    }
}
