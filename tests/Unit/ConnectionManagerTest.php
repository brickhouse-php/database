<?php

use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Exceptions\InvalidConnectionException;
use Brickhouse\Database\Sqlite\SqliteConnection;
use Brickhouse\Database\Sqlite\SqliteConnectionString;

describe('ConnectionManager::connection()', function () {
    it('throws InvalidConnectionException given empty configuration (default name)', function () {
        $config = new DatabaseConfig([]);
        $manager = new ConnectionManager($config);

        $manager->connection();
    })->throws(InvalidConnectionException::class);

    it('throws InvalidConnectionException given empty configuration (given name)', function () {
        $config = new DatabaseConfig([]);
        $manager = new ConnectionManager($config);

        $manager->connection("default");
    })->throws(InvalidConnectionException::class);

    it('returns database connection given existing connection name', function () {
        $config = new DatabaseConfig([
            "default" => SqliteConnectionString::inMemory(),
        ]);
        $manager = new ConnectionManager($config);

        $connection = $manager->connection("default");

        expect($connection::class)->toBe(SqliteConnection::class);
    });

    it('returns default database connection given null name', function () {
        $config = new DatabaseConfig([
            SqliteConnectionString::inMemory(),
        ]);
        $manager = new ConnectionManager($config);

        $connection = $manager->connection();

        expect($connection::class)->toBe(SqliteConnection::class);
    });

    it('throws InvalidConnectionException given null name without default connection', function () {
        $config = new DatabaseConfig([
            "db" => SqliteConnectionString::inMemory(),
        ]);
        $manager = new ConnectionManager($config);

        $manager->connection();
    })->throws(InvalidConnectionException::class);

    it('throws InvalidConnectionException given name with only default connection', function () {
        $config = new DatabaseConfig([
            SqliteConnectionString::inMemory(),
        ]);
        $manager = new ConnectionManager($config);

        $manager->connection("db");
    })->throws(InvalidConnectionException::class);

    it('returns same connection given repeated retrivals', function () {
        $config = new DatabaseConfig([
            SqliteConnectionString::inMemory(),
        ]);
        $manager = new ConnectionManager($config);

        $connection1 = $manager->connection();
        $connection2 = $manager->connection();

        expect($connection1->id)->toEqual($connection2->id);
    });

    it('returns new connection given different names', function () {
        $config = new DatabaseConfig([
            "db1" => SqliteConnectionString::inMemory(),
            "db2" => SqliteConnectionString::inMemory(),
        ]);
        $manager = new ConnectionManager($config);

        $connection1 = $manager->connection("db1");
        $connection2 = $manager->connection("db2");

        expect($connection1->id)->not->toEqual($connection2->id);
    });
})->group('database');
