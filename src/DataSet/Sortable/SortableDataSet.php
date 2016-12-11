<?php

declare(strict_types=1);

namespace AirMedia\Test\DataSet\Sortable;

use PHPUnit_Extensions_Database_DataSet_IDataSet as IDataSet;

/**
 * SortableDataSet.
 *
 * @author Denis Vasilev
 */
class SortableDataSet extends \PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
    private $dataset;
    private $sortBy;

    public function __construct(IDataSet $dataset, array $sortBy)
    {
        $this->dataset = $dataset;
        $this->sortBy = $sortBy;
    }

    /**
     * {@inheritdoc}
     */
    protected function createIterator($reverse = false)
    {
        $innerIterator = $reverse ? $this->dataset->getReverseIterator() : $this->dataset->getIterator();

        return new SortableTableIterator($innerIterator, $this->sortBy);
    }
}
