<?php

declare(strict_types=1);

namespace AirMedia\Test;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

/**
 * ORMTestCase.
 *
 * Based on OrmFunctionalTestCase from https://github.com/doctrine/doctrine2
 *
 * @author Denis Vasilev
 */
abstract class ORMTestCase extends TestCase
{
    /**
     * A hash of custom type names and classes which should be registered.
     *
     * @var array
     */
    protected static $customTypes = [];

    /**
     * Whether the database schema is initialized.
     *
     * @var bool
     */
    private static $initialized = false;

    /**
     * @var Connection|null
     */
    private static $sharedConn = null;

    /**
     * The metadata cache shared between all functional tests.
     *
     * @var Cache|null
     */
    private static $metadataCacheImpl = null;

    /**
     * The query cache shared between all functional tests.
     *
     * @var Cache|null
     */
    private static $queryCacheImpl = null;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DebugStack
     */
    protected $sqlLoggerStack;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        if (is_array(static::$customTypes)) {
            foreach (static::$customTypes as $name => $className) {
                if (!Type::hasType($name)) {
                    Type::addType($name, $className);
                }
            }
        }

        self::$initialized = false;
    }

    abstract protected function createMappingDriver(Configuration $config): MappingDriver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sqlLoggerStack = new DebugStack();
        $this->sqlLoggerStack->enabled = false;

        $this->em = $this->createEntityManager($this->sqlLoggerStack);

        $this->enableSqlLogger();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        if (null === $this->em) {
            return;
        }

        $this->disableSqlLogger();
        $this->em->close();

        unset($this->em, $this->sqlLoggerStack);
    }

    /**
     * {@inheritdoc}
     */
    protected function onNotSuccessfulTest($e)
    {
        if ($e instanceof \PHPUnit_Framework_AssertionFailedError) {
            throw $e;
        }

        if (isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries) > 0) {
            $queries = '';
            $i = count($this->sqlLoggerStack->queries);

            foreach (array_reverse($this->sqlLoggerStack->queries) as $query) {
                $params = array_map(function ($p) {
                    if (is_object($p)) {
                        return get_class($p);
                    } elseif (is_scalar($p)) {
                        return "'" . $p . "'";
                    }

                    return var_export($p, true);
                }, $query['params'] ?: []);

                $queries .= $i . ". SQL: '" . $query['sql'] . "' Params: " . implode(', ', $params) . PHP_EOL;
                --$i;
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

            throw new \Exception($message, (int)$e->getCode(), $e);
        }

        throw $e;
    }

    protected function enableSqlLogger()
    {
        $this->sqlLoggerStack->enabled = true;
    }

    protected function disableSqlLogger()
    {
        $this->sqlLoggerStack->enabled = false;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    protected function getProxyNamespace()
    {
        return 'DoctrineORMProxies';
    }

    private function createEntityManager(SQLLogger $logger)
    {
        if (null === self::$metadataCacheImpl) {
            self::$metadataCacheImpl = new ArrayCache();
        }

        if (null === self::$queryCacheImpl) {
            self::$queryCacheImpl = new ArrayCache();
        }

        if (null !== self::$sharedConn) {
            $em = EntityManager::create(self::$sharedConn, self::$sharedConn->getConfiguration());
        } else {
            $config = new Configuration();
            $config->setAutoGenerateProxyClasses(true);
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace($this->getProxyNamespace());
            $config->setMetadataCacheImpl(self::$metadataCacheImpl);
            $config->setQueryCacheImpl(self::$queryCacheImpl);
            $config->setMetadataDriverImpl($this->createMappingDriver($config));

            $em = EntityManager::create($this->getConnectionParams(), $config);
            self::$sharedConn = $em->getConnection();
        }

        $em->getConfiguration()->setSQLLogger($logger);

        $this->initDatabase($em);

        if (is_array(static::$customTypes) && count(static::$customTypes) > 0) {
            $platform = $em->getConnection()->getDatabasePlatform();

            foreach (array_keys(static::$customTypes) as $typeName) {
                $platform->registerDoctrineTypeMapping($typeName, $typeName);
            }
        }

        return $em;
    }

    private function getConnectionParams()
    {
        if (isset(
            $GLOBALS['db_type'],
            $GLOBALS['db_username'],
            $GLOBALS['db_password'],
            $GLOBALS['db_host'],
            $GLOBALS['db_name'],
            $GLOBALS['db_port']
        )) {
            $params = [
                'driver' => $GLOBALS['db_type'],
                'user' => $GLOBALS['db_username'],
                'password' => $GLOBALS['db_password'],
                'host' => $GLOBALS['db_host'],
                'dbname' => $GLOBALS['db_name'],
                'port' => $GLOBALS['db_port'],
            ];

            if (isset($GLOBALS['db_server'])) {
                $params['server'] = $GLOBALS['db_server'];
            }

            if (isset($GLOBALS['db_unix_socket'])) {
                $params['unix_socket'] = $GLOBALS['db_unix_socket'];
            }

            return $params;
        }

        $params = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        if (isset($GLOBALS['db_path'])) {
            $params['path'] = $GLOBALS['db_path'];
            unlink($GLOBALS['db_path']);
        }

        return $params;
    }

    private function initDatabase(EntityManager $em)
    {
        if (self::$initialized) {
            return;
        }

        $params = $this->getConnectionParams();

        unset($params['dbname'], $params['path']);

        $dbname = $em->getConnection()->getDatabase();
        $tmpConn = DriverManager::getConnection($params, $em->getConfiguration());

        if ($tmpConn->getDatabasePlatform()->supportsCreateDropDatabase()) {
            $em->getConnection()->close();

            $tmpConn->getSchemaManager()->dropAndCreateDatabase($dbname);
        } else {
            if (!in_array($dbname, $tmpConn->getSchemaManager()->listDatabases())) {
                $tmpConn->getSchemaManager()->createDatabase($dbname);
            }

            $sm = $em->getConnection()->getSchemaManager();

            $schema = $sm->createSchema();
            $stmts = $schema->toDropSql($em->getConnection()->getDatabasePlatform());

            foreach ($stmts as $stmt) {
                $em->getConnection()->exec($stmt);
            }
        }

        $tmpConn->close();

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

        self::$initialized = true;
    }
}
