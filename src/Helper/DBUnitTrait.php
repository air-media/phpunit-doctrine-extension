<?php

declare(strict_types=1);

namespace AirMedia\Test\Helper;

use AirMedia\Test\DataSet\DataSetBuilder;
use Doctrine\ORM\EntityManagerInterface;

/**
 * DBUnitTrait.
 *
 * @author Denis Vasilev
 */
trait DBUnitTrait
{
    use \PHPUnit_Extensions_Database_TestCase_Trait;

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
        return \PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT(true);
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
