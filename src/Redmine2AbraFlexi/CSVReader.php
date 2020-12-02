<?php

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
     *
     * @param type $sourceFile
     */
    public function __construct($sourceFile)
    {
        parent::__construct();
        if (!empty($sourceFile)) {
            $this->loadFromCSV($sourceFile);
        }
    }

    public function loadFromCSV($sourceFile)
    {
        $this->sourceFile = $sourceFile;
        $rows             = explode("\n", file_get_contents($sourceFile));
        $this->columns    = explode(',', $rows[0]);
        unset($rows[0]);
        foreach ($rows as $rowId => $rowText) {
            $data[$rowId] = explode(',', $rowText);
        }
        if ($this->setData($data)) {
            $this->sourceFile = $sourceFile;
        }
    }
}