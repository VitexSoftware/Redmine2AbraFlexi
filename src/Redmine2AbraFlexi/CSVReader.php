<?php
/**
 * Redmine2AbraFlexi - Generate AbraFlexi invoice from Redmine's workhours
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2023 Vitex Software
 */

namespace Redmine2AbraFlexi;

/**
 * Description of CSVReader
 *
 * @author vitex
 */
class CSVReader extends \Ease\Brick
{

    public $sourceFile = null;
    public $columns = [];

    /**
     * CSV Reader class
     * 
     * @param string $sourceFile
     */
    public function __construct($sourceFile)
    {
        parent::__construct();
        if (file_exists($sourceFile)) {
            $this->loadFromCSV($sourceFile);
        }
    }

    /**
     * Populate object by source file 
     * 
     * @param string $sourceFile
     */
    public function loadFromCSV($sourceFile)
    {
        $this->sourceFile = $sourceFile;
        $rows = explode("\n", file_get_contents($sourceFile));
        $this->columns = explode(',', $rows[0]);
        unset($rows[0]);
        $data = [];
        foreach ($rows as $rowId => $rowText) {
            $data[$rowId] = explode(',', $rowText);
        }
        if ($this->setData($data)) {
            $this->sourceFile = $sourceFile;
        }
    }
}