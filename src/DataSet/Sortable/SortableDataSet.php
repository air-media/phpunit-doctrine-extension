<?php

declare(strict_types=1);

namespace AirMedia\Test\DataSet\Sortable;

use PHPUnit\DbUnit\DataSet\AbstractDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;

/**
 * SortableDataSet.
 *
 * @author Denis Vasilev
 */
class SortableDataSet extends AbstractDataSet
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
