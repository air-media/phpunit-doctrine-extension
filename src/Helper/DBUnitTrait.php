<?php

declare(strict_types=1);

namespace AirMedia\Test\Helper;

use AirMedia\Test\DataSet\DataSetBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\DbUnit\Operation\Factory;
use PHPUnit\DbUnit\TestCaseTrait;

/**
 * DBUnitTrait.
 *
 * @author Denis Vasilev
 */
trait DBUnitTrait
{
    use TestCaseTrait;

    abstract protected function getEntityManager(): EntityManagerInterface;

    protected function createDataSetBuilder($useDefaultReplacements = true): DataSetBuilder
    {
        $builder = new DataSetBuilder();

        if ($useDefaultReplacements) {
            $builder->addFullReplacement('##NULL##', null);
            $builder->addFullReplacement('##NOW##', date('Y-m-d H:i:s'));
        }

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetUpOperation()
    {
        return Factory::CLEAN_INSERT(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnection()
    {
        $conn = $this->getEntityManager()->getConnection();
        $pdo = $conn->getWrappedConnection();

        return $this->createDefaultDBConnection($pdo);
    }
}
