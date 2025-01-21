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
 * Description of Engine.
 *
 * @author vitex
 */
class FakturaVydana extends \AbraFlexi\FakturaVydana
{
    /**
     * Do not require SQL.
     *
     * @var null
     */
    public $myTable;

    /**
     * List of itemsIncluded.
     *
     * @var array<int>
     */
    private array $itemsIncluded = [];

    /**
     * List of projectsIncluded.
     *
     * @var array<int>
     */
    private array $projectsIncluded = [];

    /**
     * AbraFlexi Invoice.
     *
     * @param null|mixed            $init
     * @param array<string, string> $options Connection settings override
     */
    public function __construct($init = null, array $options = [])
    {
        parent::__construct($init, $options = []);

        if (!\array_key_exists('typDokl', $init)) {
            $this->setDataValue(
                'typDokl',
                \AbraFlexi\Functions::code(\Ease\Shared::instanced()->getConfigValue('ABRAFLEXI_TYP_FAKTURY')),
            );
        }
    }

    /**
     * Fill Invoice items from datasource.
     *
     * @param CSVReader $dataSource
     */
    public function takeItemsFromCSV($dataSource): void
    {
        $lastProject = null;
        $itemsData = [];

        foreach ($dataSource->getData() as $rowId => $csvData) {
            if (empty($csvData) || (\count($csvData) < 5)) {
                continue;
            }

            if (self::stripComas($csvData[5]) !== $lastProject) {
                $lastProject = self::stripComas($csvData[5]);
            }

            $nazev = self::stripComas($csvData[8]);

            if (isset($itemsData[$lastProject][$nazev])) {
                $itemsData[$lastProject][$nazev]['mnozMj'] += (float) self::stripComas($csvData[10]);
            } else {
                $itemsData[$lastProject][$nazev] = [
                    'typPolozkyK' => 'typPolozky.katalog',
                    'poznam' => self::stripComas($csvData[9]),
                    'nazev' => $nazev,
                    'mnozMj' => (float) self::stripComas($csvData[10]),
                    'cenik' => \AbraFlexi\Functions::code(\Ease\Shared::instanced()->getConfigValue('ABRAFLEXI_CENIK'))];
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            $this->addArrayToBranch(
                ['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: '.$projectName],
                'polozkyFaktury',
            );

            foreach ($projectData as $taskName => $taskData) {
                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
        }
    }

    /**
     * Remove Initial and Ending " from string.
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
     * @param array<mixed> $timeEntriesRaw
     */
    public function takeItemsFromArray(array $timeEntriesRaw): void
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
                    $itemsData[$projectName][$nazev]['mnozMj'] += (float) $timeEntry['hours'];
                } else {
                    if (!\array_key_exists($rowId, $this->itemsIncluded)) {
                        $itemsData[$projectName][$nazev] = [
                            //                            'id' => 'ext:redmine:'.$rowId,
                            'typPolozkyK' => 'typPolozky.katalog',
                            'nazev' => $nazev,
                            'popis' => $timeEntry['comments'],
                            'mnozMj' => (float) $timeEntry['hours'],
                            'cenik' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_CENIK'))];
                        $this->itemsIncluded[$rowId] = $rowId;
                    }
                }
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            if (!\array_key_exists($projectName, $this->projectsIncluded)) {
                $this->addArrayToBranch(
                    ['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: '.$projectName],
                    'polozkyFaktury',
                ); // Task Title as Heading/TextRow
                $this->projectsIncluded[$projectName] = $projectName;

                foreach ($projectData as $taskName => $taskData) {
                    $this->addArrayToBranch($taskData, 'polozkyFaktury');
                }
            }
        }
    }
}
