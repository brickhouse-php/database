<?php

namespace Brickhouse\Database\Tests;

use Brickhouse\Database\Postgres\PostgresConnection;
use Brickhouse\Database\Postgres\PostgresConnectionString;
use Testcontainers\Container\PostgresContainer;

abstract class PostgresTestCase extends TestCase
{
    private static PostgresContainer $container;

    private static PostgresConnection $connection;

    public static function setUpBeforeClass(): void
    {
        self::$container = PostgresContainer::make('15.0', 'password');
        self::$container->withPostgresDatabase('database');
        self::$container->withPostgresUser('username');
        self::$container->withPort(17412, 5432);
        self::$container->run();

        self::$connection = new PostgresConnection(
            new PostgresConnectionString(
                'localhost',
                17412,
                'database',
                'username',
                'password'
            )
        );
    }

    public static function tearDownAfterClass(): void
    {
        self::$container->stop();
    }

    /**
     * Gets the database connection to the Postgres container.
     *
     * @return PostgresConnection
     */
    public function connection(): PostgresConnection
    {
        return self::$connection;
    }
}
