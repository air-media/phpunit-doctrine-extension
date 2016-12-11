<?php

declare(strict_types=1);

namespace AirMedia\Test\DataSet\Sortable;

use PHPUnit_Extensions_Database_DataSet_ITableIterator as ITableIterator;

/**
 * SortableTableIterator.
 *
 * @author Denis Vasilev
 */
class SortableTableIterator implements ITableIterator
{
    private $innerIterator;
    private $sortBy;

    public function __construct(ITableIterator $iterator, array $sortBy)
    {
        $this->innerIterator = $iterator;
        $this->sortBy = $sortBy;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        /** @var \PHPUnit_Extensions_Database_DataSet_ITable $table */
        $table = $this->innerIterator->current();
        $tableName = $table->getTableMetaData()->getTableName();

        if (isset($this->sortBy[$tableName])) {
            $table = new SortableTable($table, $this->sortBy[$tableName]);
        }

        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->current()->getTableMetaData()->getTableName();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->innerIterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->innerIterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->innerIterator->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->current();
    }

    /**
     * {@inheritdoc}
     */
    public function getTableMetaData()
    {
        return $this->current()->getTableMetaData();
    }
}
