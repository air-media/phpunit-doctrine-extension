<?php

declare(strict_types=1);

namespace AirMedia\Test;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use function in_array;
use function unlink;

class DatabaseUtil
{
    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * @var bool
     */
    private static $schemaCreated = false;

    /**
     * @return mixed[]
     */
    public static function getConnectionParams(): array
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

    public static function initDatabase(): void
    {
        if (self::$initialized) {
            return;
        }

        $params = self::getConnectionParams();
        $dbname = $params['dbname'];

        unset($params['dbname'], $params['path']);

        $tmpConn = DriverManager::getConnection($params);

        if ($tmpConn->getDatabasePlatform()->supportsCreateDropDatabase()) {
            $tmpConn->getSchemaManager()->dropAndCreateDatabase($dbname);
        } else {
            if (!in_array($dbname, $tmpConn->getSchemaManager()->listDatabases())) {
                $tmpConn->getSchemaManager()->createDatabase($dbname);
            }

            $conn = DriverManager::getConnection(self::getConnectionParams());
            $sm = $conn->getSchemaManager();

            $schema = $sm->createSchema();
            $stmts = $schema->toDropSql($conn->getDatabasePlatform());

            foreach ($stmts as $stmt) {
                $conn->exec($stmt);
            }

            $conn->close();
        }

        $tmpConn->close();

        self::$initialized = true;
    }

    public static function setUpSchema(EntityManager $em): void
    {
        if (self::$schemaCreated) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

        self::$schemaCreated = true;
    }
}
