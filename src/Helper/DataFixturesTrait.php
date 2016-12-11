<?php

declare(strict_types=1);

namespace AirMedia\Test\Helper;

use AirMedia\Test\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;

/**
 * DataFixturesTrait.
 *
 * @author Denis Vasilev
 */
trait DataFixturesTrait
{
    private static $fixtureLoaded = false;

    public static function setUpBeforeClass()
    {
        self::$fixtureLoaded = false;

        parent::setUpBeforeClass();
    }

    abstract protected function getEntityManager(): EntityManagerInterface;

    public function loadFixtures(array $fixtures, $override = false)
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
