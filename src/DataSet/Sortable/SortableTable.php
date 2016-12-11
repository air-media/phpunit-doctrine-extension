<?php

declare(strict_types=1);

namespace AirMedia\Test\DataSet\Sortable;

use PHPUnit_Extensions_Database_DataSet_AbstractTable as AbstractTable;
use PHPUnit_Extensions_Database_DataSet_ITable as ITable;

/**
 * SortableTable.
 *
 * @author Denis Vasilev
 */
class SortableTable extends AbstractTable
{
    private $innerTable;
    private $sortBy;

    public function __construct(ITable $table, $sortBy)
    {
        $this->innerTable = $table;
        $this->sortBy = (array)$sortBy;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableMetaData()
    {
        return $this->innerTable->getTableMetaData();
    }

    /**
     * {@inheritdoc}
     */
    public function getRowCount()
    {
        return $this->innerTable->getRowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($row, $column)
    {
        $this->sort();

        return parent::getValue($row, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function getRow($row)
    {
        $this->sort();

        return parent::getRow($row);
    }

    /**
     * {@inheritdoc}
     */
    public function matches(ITable $other)
    {
        $this->sort();

        return parent::matches($other);
    }

    /**
     * {@inheritdoc}
     */
    public function assertContainsRow(array $row)
    {
        $this->sort();

        return parent::assertContainsRow($row);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->sort();

        return parent::__toString();
    }

    /**
     * {@inheritdoc}
     */
    private function sort()
    {
        if (is_array($this->data)) {
            return;
        }

        $this->data = [];
        $rowCount = $this->getRowCount();
        $columns = $this->getTableMetaData()->getColumns();

        for ($row = 0; $row < $rowCount; ++$row) {
            $data = [];

            foreach ($columns as $columnName) {
                $data[$columnName] = $this->innerTable->getValue($row, $columnName);
            }

            $this->data[] = $data;
        }

        usort($this->data, function (array $a, array $b) {
            foreach ($this->sortBy as $name) {
                $left = (string)$a[$name];
                $right = (string)$b[$name];

                if ($left < $right) {
                    return -1;
                } elseif ($left > $right) {
                    return 1;
                }
            }

            return 0;
        });
    }
}
