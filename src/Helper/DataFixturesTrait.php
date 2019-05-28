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
    /**
     * @var bool
     */
    private static $fixtureLoaded = false;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureLoaded = false;

        parent::setUpBeforeClass();
    }

    abstract protected function getEntityManager(): EntityManagerInterface;

    /**
     * @param FixtureInterface[]|string[] $fixtures
     * @param bool                        $override
     */
    public function loadFixtures(array $fixtures, bool $override = false): void
    {
        if ($override) {
            self::$fixtureLoaded = false;
        }

        if (self::$fixtureLoaded) {
            return;
        }

        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (is_string($fixture)) {
                $fixture = new $fixture();
            }

            $loader->addFixture($fixture);
        }

        $em = $this->getEntityManager();

        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        self::$fixtureLoaded = true;
    }
}
