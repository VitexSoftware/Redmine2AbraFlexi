<?php

/**
 * Redmine2AbraFlexi - Generate AbraFlexi invoice from Redmine's workhours
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2023 Vitex Software
 */

namespace Redmine2AbraFlexi;

/**
 * Description of Engine
 *
 * @author vitex
 */
class FakturaVydana extends \AbraFlexi\FakturaVydana
{

    /**
     * Do not require SQL
     * @var null
     */
    public $myTable = null;

    /**
     *
     * @var array 
     */
    private $itemsIncluded = [];

    /**
     *
     * @var array 
     */
    private $projectsIncluded = [];

    /**
     * AbraFlexi Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($init = null, $options = [])
    {
        parent::__construct($init, $options = []);
        if (!array_key_exists('typDokl', $init)) {
            $this->setDataValue('typDokl',
                    self::code(\Ease\Shared::instanced()->getConfigValue('ABRAFLEXI_TYP_FAKTURY')));
        }
    }

    /**
     * Fill Invoice items from datasource
     * 
     * @param CSVReader $dataSource
     */
    public function takeItemsFromCSV($dataSource)
    {
        $lastProject = null;
        $itemsData = [];
        foreach ($dataSource->getData() as $rowId => $csvData) {
            if (empty($csvData) || (count($csvData) < 5)) {
                continue;
            }
            if (self::stripComas($csvData[5]) != $lastProject) {
                $lastProject = self::stripComas($csvData[5]);
            }

            $nazev = self::stripComas($csvData[8]);
            if (isset($itemsData[$lastProject][$nazev])) {
                $itemsData[$lastProject][$nazev]['mnozMj'] += floatval(self::stripComas($csvData[10]));
            } else {
                $itemsData[$lastProject][$nazev] = [
                    'typPolozkyK' => 'typPolozky.katalog',
                    'poznam' => self::stripComas($csvData[9]),
                    'nazev' => $nazev,
                    'mnozMj' => floatval(self::stripComas($csvData[10])),
                    'cenik' => self::code(\Ease\Shared::instanced()->getConfigValue('ABRAFLEXI_CENIK'))];
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: ' . $projectName],
                    'polozkyFaktury');
            foreach ($projectData as $taskName => $taskData) {
                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
        }
    }

    /**
     * Remove Initial and Ending " from string
     *
     * @param string $string
     * 
     * @return string
     */
    public static function stripComas($string)
    {
        return substr($string, 1, -1);
    }

    /**
     * 
     * @param array $timeEntriesRaw
     */
    public function takeItemsFromArray($timeEntriesRaw)
    {
        $itemsData = [];
        $timeEntries = [];
        foreach ($timeEntriesRaw as $rowId => $timeEntryRaw) {
            $timeEntries[$timeEntryRaw['project']][$rowId] = $timeEntryRaw;
        }

        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            foreach ($projectTimeEntries as $rowId => $timeEntry) {
                $nazev = $timeEntry['issue'];
                if (isset($itemsData[$projectName][$nazev])) {
                    $itemsData[$projectName][$nazev]['mnozMj'] += floatval($timeEntry['hours']);
                } else {
                    if (!array_key_exists($rowId, $this->itemsIncluded)) {
                        $itemsData[$projectName][$nazev] = [
//                            'id' => 'ext:redmine:'.$rowId,
                            'typPolozkyK' => 'typPolozky.katalog',
                            'nazev' => $nazev,
                            'popis' => $timeEntry['comments'],
                            'mnozMj' => floatval($timeEntry['hours']),
                            'cenik' => self::code(\Ease\Functions::cfg('ABRAFLEXI_CENIK'))];
                        $this->itemsIncluded[$rowId] = $rowId;
                    }
                }
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            if (!array_key_exists($projectName, $this->projectsIncluded)) {
                $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: ' . $projectName],
                        'polozkyFaktury'); // Task Title as Heading/TextRow
                $this->projectsIncluded[$projectName] = $projectName;
                foreach ($projectData as $taskName => $taskData) {
                    $this->addArrayToBranch($taskData, 'polozkyFaktury');
                }
            }
        }
    }
}