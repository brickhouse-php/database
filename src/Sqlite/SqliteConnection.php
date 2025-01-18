<?php

namespace Brickhouse\Database\Sqlite;

use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\Grammar;

class SqliteConnection extends DatabaseConnection
{
    /**
     * Gets the internal grammar instance.
     *
     * @var SqliteGrammar
     */
    private readonly SqliteGrammar $internalGrammar;

    /**
     * @inheritDoc
     */
    public Grammar $grammar {
        get => $this->internalGrammar;
    }

    /**
     * Connect to the database using PHP's PDO interface.
     *
     * @param SqliteConnectionString        $connectionString
     */
    public function __construct(
        SqliteConnectionString $connectionString,
    ) {
        parent::__construct($connectionString);

        $this->internalGrammar = new SqliteGrammar;
    }

    /**
     * Creates a new  SQLite connection which points to an in-memory database.
     */
    public static function inMemory(): SqliteConnection
    {
        return new SqliteConnection(SqliteConnectionString::inMemory());
    }
}
