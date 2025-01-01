<?php

declare(strict_types=1);

/**
 * This file is part of the xls2abralexi package
 *
 * https://multiflexi.eu/
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Redmine2AbraFlexi;

/**
 * Description of CSVReader.
 *
 * @author vitex
 */
class CSVReader extends \Ease\Brick
{
    public string $sourceFile;

    /**
     * List of columns to be read.
     *
     * @var array<string>
     */
    public array $columns = [];

    /**
     * CSV Reader class.
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
     * Populate object by source file.
     */
    public function loadFromCSV(string $sourceFile): void
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
