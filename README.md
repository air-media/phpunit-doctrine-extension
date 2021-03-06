# Doctrine Extension for PHPUnit

Provides the base TestCase for functional ORM\DB tests.

Works with RDBMS:

* PostgreSQL > 9.4
* SQLite

## Installation

```bash
$ composer require airmedia\phpunit-doctrine-extension --dev
```

### Support PostgreSQL

For work with PostgreSQL you need to modify your configuration file
 (`phpunit.xml`) for PHPUnit. Add some variables to configure connection
 to PostgreSQL server. For example:

```xml
    <php>
        <var name="db_type" value="pdo_pgsql" />
        <var name="db_host" value="localhost" />
        <var name="db_username" value="postgres" />
        <var name="db_password" value="" />
        <var name="db_name" value="my_database_tests" />
        <var name="db_port" value="5432" />
    </php>
```

If these options are missing then will be used driver for SQLite as the fallback.

## Usage

You must inherit from `AirMedia\Test\OrmTestCase` and implement `createMappingDriver`
to provide configured mapping driver.

Also you may override the static field `$customTypes` to define the custom types for Doctrine.

Example:

```php
<?php

use AirMedia\Test\ORMTestCase;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Ramsey\Uuid\Doctrine\UuidType;

class RepositoryTestCase extends ORMTestCase
{
    static protected $customTypes = [
        UuidType::NAME => UuidType::class,
    ];
    
    public function testAnything()
    {
        $repository = $this->em->getRepository('Acme\Entity\User');
        
        // Action and asserts...
    }
    
    protected function createMappingDriver(Configuration $config): MappingDriver
    {
        $annotationDriver = $config->newDefaultAnnotationDriver([
            realpath(__DIR__ . '/../src/Entity'),
        ], false);
        
        $driver = new MappingDriverChain();
        $driver->addDriver($annotationDriver, 'Acme\Entity');

        return $driver;
    }
}
```

### [Doctrine Data Fixtures Extension](https://github.com/doctrine/data-fixtures)

You may populate your database throught the data fixtures 
using trait `AirMedia\Test\Helper\DataFixturesTrait`.

```php
<?php

use AirMedia\Test\ORMTestCase;
use AirMedia\Test\Helper\DataFixturesTrait;

class FooTestCase extends ORMTestCase
{
    use DataFixturesTrait;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->loadFixtures([
            new \Acme\DataFixtures\UserFixture(),
            'Acme\DataFixtures\GroupFixture', // or class name
        ]);
    }
}
```

## License

This package is licensed using the MIT License.
