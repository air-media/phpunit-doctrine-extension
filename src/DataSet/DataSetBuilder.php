<?php

declare(strict_types=1);

namespace AirMedia\Test\DataSet;

use PHPUnit\DbUnit\DataSet\Filter;
use PHPUnit\DbUnit\DataSet\FlatXmlDataSet;
use PHPUnit\DbUnit\DataSet\IDataSet;
use PHPUnit\DbUnit\DataSet\ReplacementDataSet;

/**
 * DataSetBuilder.
 *
 * @author Denis Vasilev
 */
class DataSetBuilder
{
    private $fullReplacements = [];
    private $sortBy = [];
    private $excludeColumns = [];

    public function addFullReplacement($key, $value): self
    {
        $this->fullReplacements[$key] = $value;

        return $this;
    }

    public function addSortBy($tableName, $columns): self
    {
        $this->sortBy[$tableName] = (array)$columns;

        return $this;
    }

    public function setExcludeColumnsForTable($tableName, array $columns): self
    {
        $this->excludeColumns[$tableName] = $columns;

        return $this;
    }

    /**
     * Creates the dataset.
     *
     * @param IDataSet|string $dataset The object or path to xml file (FlatXml)
     *
     * @throws \RuntimeException
     *
     * @return IDataSet
     */
    public function createDataSet($dataset): IDataSet
    {
        if (is_string($dataset)) {
            $dataset = new FlatXmlDataSet($dataset);
        }

        if (!$dataset instanceof IDataSet) {
            throw new \RuntimeException(sprintf('Expected path to XML file or instance of %s.', IDataSet::class));
        }

        if (count($this->excludeColumns) > 0) {
            $dataset = new Filter($dataset, $this->excludeColumns);
        }

        if (count($this->fullReplacements) > 0) {
            $dataset = new ReplacementDataSet($dataset, $this->fullReplacements);
        }

        if (count($this->sortBy) > 0) {
            $dataset = new Sortable\SortableDataSet($dataset, $this->sortBy);
        }

        return $dataset;
    }
}
