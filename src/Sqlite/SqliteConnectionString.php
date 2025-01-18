<?php

namespace Brickhouse\Database\Sqlite;

use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\DatabaseConnectionString;

final class SqliteConnectionString implements DatabaseConnectionString
{
    /**
     * @inheritDoc
     */
    public string $connectionString {
        get => "sqlite:{$this->path}";
    }

    /**
     * @inheritDoc
     */
    public null|string $username {
        get => null;
    }

    /**
     * @inheritDoc
     */
    public null|string $password {
        get => null;
    }

    public function __construct(
        #[\SensitiveParameter]
        public string $path,
    ) {}

    /**
     * @inheritDoc
     */
    public function connect(): DatabaseConnection
    {
        return new SqliteConnection($this);
    }

    /**
     * Creates a new  SQLite connection string which points to an in-memory database.
     */
    public static function inMemory(): SqliteConnectionString
    {
        return new SqliteConnectionString(":memory:");
    }
}
