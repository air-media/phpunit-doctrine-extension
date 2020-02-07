<?php

declare(strict_types=1);

namespace AirMedia\Test\Helper;

use AirMedia\Test\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use function is_string;

trait DataFixturesTrait
{
    abstract protected function getEntityManager(): EntityManagerInterface;

    /**
     * @param FixtureInterface[]|string[] $fixtures
     */
    public function loadFixtures(array $fixtures, bool $append = false): void
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (is_string($fixture)) {
                $fixture = new $fixture();
            }

            $loader->addFixture($fixture);
        }

        $executor = $this->getExecutor($this->getEntityManager());
        $executor->execute($loader->getFixtures(), $append);
    }

    protected function getExecutor(EntityManagerInterface $em): ORMExecutor
    {
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        return new ORMExecutor($em, $purger);
    }
}
