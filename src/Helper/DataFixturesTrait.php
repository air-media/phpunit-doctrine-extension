<?php

declare(strict_types=1);

namespace AirMedia\Test\Helper;

use AirMedia\Test\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use function count;
use function is_string;

trait DataFixturesTrait
{
    /**
     * @var FixtureInterface[]|string[]
     */
    public static $sharedFixtures = [];

    /**
     * @var bool
     */
    private static $sharedFixturesLoaded = false;

    public static function setUpBeforeClass(): void
    {
        self::$sharedFixturesLoaded = false;

        parent::setUpBeforeClass();
    }

    abstract protected function getEntityManager(): EntityManagerInterface;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$sharedFixturesLoaded) {
            return;
        }

        if (count(self::$sharedFixtures) > 0) {
            $this->loadFixtures(self::$sharedFixtures);
        }

        self::$sharedFixturesLoaded = true;
    }

    /**
     * @param FixtureInterface[]|string[] $fixtures
     * @param bool                        $append
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

        self::$sharedFixturesLoaded = true;
    }

    protected function getExecutor(EntityManagerInterface $em): ORMExecutor
    {
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        return new ORMExecutor($em, $purger);
    }
}
