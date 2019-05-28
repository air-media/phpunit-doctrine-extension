<?php

declare(strict_types=1);

namespace AirMedia\Test\Purger;

use Doctrine\Common\DataFixtures\Purger\ORMPurger as BaseORMPurger;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use function sprintf;

class ORMPurger extends BaseORMPurger
{
    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        parent::purge();

        $conn = $this->getObjectManager()->getConnection();

        if (!($conn->getDatabasePlatform() instanceof PostgreSqlPlatform)) {
            return;
        }

        $sequences = $conn->fetchAll("SELECT relname FROM pg_class WHERE relkind='S'");

        foreach ($sequences as $sequence) {
            $conn->exec(sprintf('ALTER SEQUENCE %s RESTART WITH 1', $sequence['relname']));
        }
    }
}
