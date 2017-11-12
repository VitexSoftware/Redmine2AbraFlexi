<?php

namespace Redmine2FlexiBee;

/**
 * Description of Engine
 *
 * @author vitex
 */
class FakturaVydana extends \FlexiPeeHP\FakturaVydana
{
    /**
     * Do not require SQL
     * @var null
     */
    public $myTable = null;

    /**
     * FlexiBee Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($options = [])
    {
        parent::__construct();
        $this->setDataValue('typDokl',
            self::code(\Ease\Shared::instanced()->getConfigValue('FLEXIBEE_TYP_FAKTURY')));
    }

    /**
     * Fill Invoice items from datasource
     * 
     * @param CSVReader $dataSource
     */
    public function takeItemsFromCSV($dataSource)
    {
        $lastProject = null;
        $itemsData   = [];
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
                    'cenik' => self::code(\Ease\Shared::instanced()->getConfigValue('FLEXIBEE_CENIK'))];
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: '.$projectName],
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
     * Přidá položku do objednávky
     *
     * @param int|string $orderID
     * @param array      $itemData
     *
     * @return boolean result
     */
    public function addItemToOrder($orderID, $itemData)
    {
        $this->dataReset();
        $this->setMyKey($orderID);
        $itemData['typDokl'] = $this->configuration['kodvydaneobjednavky'];
        $this->addArrayToBranch($itemData, 'polozkyDokladu');
        $this->insertToFlexiBee();
        return $this->lastResponseCode == 201;
    }

    public function takeItemsFromArray($timeEntriesRaw)
    {
        $itemsData = [];
        foreach ($timeEntriesRaw as $rowId => $timeEntryRaw) {
            $timeEntries[$timeEntryRaw['project']][$rowId] = $timeEntryRaw;
        }

        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            foreach ($projectTimeEntries as $rowId => $timeEntry) {
                $nazev = $timeEntry['issue'];

                if (isset($itemsData[$projectName][$nazev])) {
                    $itemsData[$projectName][$nazev]['mnozMj'] += floatval($timeEntry['hours']);
                } else {
                    $itemsData[$projectName][$nazev] = [
                        'typPolozkyK' => 'typPolozky.katalog',
                        'poznam' => $timeEntry['comments'],
                        'nazev' => $nazev,
                        'mnozMj' => floatval($timeEntry['hours']),
                        'cenik' => self::code(\Ease\Shared::instanced()->getConfigValue('FLEXIBEE_CENIK'))];
                }
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: '.$projectName],
                'polozkyFaktury');

            foreach ($projectData as $taskName => $taskData) {
                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
        }
    }
}