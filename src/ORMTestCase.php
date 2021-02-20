<?php

declare(strict_types=1);

namespace AirMedia\Test;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Throwable;
use function array_keys;
use function array_map;
use function array_reverse;
use function count;
use function get_class;
use function implode;
use function is_array;
use function is_object;
use function is_scalar;
use function strpos;
use function sys_get_temp_dir;
use function var_export;
use const PHP_EOL;

/**
 * ORMTestCase.
 *
 * Based on OrmFunctionalTestCase from https://github.com/doctrine/doctrine2
 */
abstract class ORMTestCase extends TestCase
{
    /**
     * @var DebugStack
     */
    protected $sqlLoggerStack;

    /**
     * A hash of custom type names and classes which should be registered.
     *
     * @var array<string,string>
     */
    protected static $customTypes = [];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Connection|null
     */
    private static $sharedConn;

    /**
     * The metadata cache shared between all functional tests.
     *
     * @var Cache|null
     */
    private static $metadataCacheImpl;

    /**
     * The query cache shared between all functional tests.
     *
     * @var Cache|null
     */
    private static $queryCacheImpl;

    /**
     * @var bool
     */
    private static $schemaCreated = false;

    abstract protected function createMappingDriver(Configuration $config): MappingDriver;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        if (is_array(static::$customTypes)) {
            foreach (static::$customTypes as $name => $className) {
                if (Type::hasType($name)) {
                    Type::overrideType($name, $className);
                } else {
                    Type::addType($name, $className);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sqlLoggerStack = new DebugStack();
        $this->sqlLoggerStack->enabled = false;

        DatabaseUtil::initDatabase();

        $this->em = $this->createEntityManager($this->sqlLoggerStack);

        if (!self::$schemaCreated) {
            $this->setUpSchema();

            self::$schemaCreated = true;
        }

        $this->enableSqlLogger();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->em === null) {
            return;
        }

        $this->disableSqlLogger();
        $this->em->close();

        unset($this->em, $this->sqlLoggerStack);
    }

    /**
     * {@inheritdoc}
     */
    protected function onNotSuccessfulTest(Throwable $e): void
    {
        if ($e instanceof AssertionFailedError) {
            throw $e;
        }

        if (isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries) > 0) {
            $queries = '';
            $i = count($this->sqlLoggerStack->queries);

            foreach (array_reverse($this->sqlLoggerStack->queries) as $query) {
                $params = array_map(static function ($p) {
                    if (is_object($p)) {
                        return get_class($p);
                    }

                    if (is_scalar($p)) {
                        return "'" . $p . "'";
                    }

                    return var_export($p, true);
                }, $query['params'] ?: []);

                $queries .= $i . ". SQL: '" . $query['sql'] . "' Params: " . implode(', ', $params) . PHP_EOL;
                $i--;
            }

            $trace = $e->getTrace();
            $traceMsg = '';

            foreach ($trace as $part) {
                if (isset($part['file'])) {
                    if (strpos($part['file'], 'PHPUnit/') !== false) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'] . ':' . $part['line'] . PHP_EOL;
                }
            }

            $message = '[' . get_class($e) . '] ' . $e->getMessage() . PHP_EOL . PHP_EOL .
                'With queries:' . PHP_EOL . $queries . PHP_EOL . 'Trace:' . PHP_EOL . $traceMsg;

            throw new Exception($message, (int)$e->getCode(), $e);
        }

        throw $e;
    }

    protected function setUpSchema(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    protected function enableSqlLogger(): void
    {
        $this->sqlLoggerStack->enabled = true;
    }

    protected function disableSqlLogger(): void
    {
        $this->sqlLoggerStack->enabled = false;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    protected function getProxyNamespace(): string
    {
        return 'DoctrineORMProxies';
    }

    private function createEntityManager(SQLLogger $logger): EntityManager
    {
        if (self::$metadataCacheImpl === null) {
            self::$metadataCacheImpl = new ArrayCache();
        }

        if (self::$queryCacheImpl === null) {
            self::$queryCacheImpl = new ArrayCache();
        }

        if (self::$sharedConn !== null) {
            $em = EntityManager::create(self::$sharedConn, self::$sharedConn->getConfiguration());
        } else {
            $config = new Configuration();
            $config->setAutoGenerateProxyClasses(true);
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace($this->getProxyNamespace());
            $config->setMetadataCacheImpl(self::$metadataCacheImpl);
            $config->setQueryCacheImpl(self::$queryCacheImpl);
            $config->setMetadataDriverImpl($this->createMappingDriver($config));

            $em = EntityManager::create(DatabaseUtil::getConnectionParams(), $config);
            self::$sharedConn = $em->getConnection();
        }

        $em->getConfiguration()->setSQLLogger($logger);

        if (is_array(static::$customTypes) && count(static::$customTypes) > 0) {
            $platform = $em->getConnection()->getDatabasePlatform();

            foreach (array_keys(static::$customTypes) as $typeName) {
                $platform->registerDoctrineTypeMapping($typeName, $typeName);
            }
        }

        return $em;
    }
}
