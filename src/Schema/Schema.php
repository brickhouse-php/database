<?php

namespace Brickhouse\Database\Schema;

use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Log\Log;

class Schema
{
    /**
     * Gets the database connection the schema reflects.
     *
     * @var DatabaseConnection
     */
    public readonly DatabaseConnection $connection;

    /**
     * Gets whether the schema should pretend to apply migrations.
     */
    public private(set) bool $pretending = false;

    /**
     * Creates a new `Schema` instance on the given connection.
     *
     * @param null|string|DatabaseConnection    $connection     The database connection the schema should reflect.
     */
    public function __construct(
        null|string|DatabaseConnection $connection = null,
    ) {
        if (is_null($connection) || is_string($connection)) {
            $connectionManager = resolve(ConnectionManager::class);
            $connection = $connectionManager->connection($connection);
        }

        $this->connection = $connection;
    }

    /**
     * Defines whether the schema should pretend to apply migrations.
     *
     * @param bool $pretend
     *
     * @return void
     */
    public function pretend(bool $pretend = true): void
    {
        $this->pretending = $pretend;
    }

    /**
     * Creates a new blueprint with the given callback.
     *
     * @param string|\Closure       $table
     * @param null|\Closure         $callback
     *
     * @return Blueprint
     */
    protected function createBlueprint(string|\Closure $table, null|\Closure $callback = null): Blueprint
    {
        if ($table instanceof \Closure) {
            $callback = $table;
            $table = null;
        }

        return resolve(Blueprint::class, [
            'table' => $table ?? '',
            'connection' => $this->connection,
            'callback' => $callback,
        ]);
    }

    /**
     * Creates a new table with the given schema.
     *
     * @param string        $table          Name of the new table.
     * @param \Closure      $callback       Callback to create the table schema.
     *
     * @return void
     */
    public function create(string $table, \Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table, function (Blueprint $blueprint) use ($callback) {
            $callback($blueprint);
            $blueprint->create();
        });

        $this->execute($blueprint);
    }

    /**
     * Creates a new table with the given schema, if the table doesn't already exist.
     *
     * @param string        $table          Name of the new table.
     * @param \Closure      $callback       Callback to create the table schema.
     *
     * @return void
     */
    public function createIfNotExists(string $table, \Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table, function (Blueprint $blueprint) use ($callback) {
            $callback($blueprint);
            $blueprint->createIfNotExists();
        });

        $this->execute($blueprint);
    }

    /**
     * Alters an existing table.
     *
     * @param string        $table          Name of the table.
     * @param \Closure      $callback       Callback to alter the table schema.
     *
     * @return void
     */
    public function table(string $table, \Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table, function (Blueprint $blueprint) use ($callback) {
            $callback($blueprint);
            $blueprint->alter();
        });

        $this->execute($blueprint);
    }

    /**
     * Drops a table.
     *
     * @param string        $table          Name of the table to drop.
     *
     * @return void
     */
    public function drop(string $table): void
    {
        $this->execute(
            $this->createBlueprint($table, fn(Blueprint $blueprint) => $blueprint->drop())
        );
    }

    /**
     * Drops a table, if it exists.
     *
     * @param string        $table          Name of the table to drop.
     *
     * @return void
     */
    public function dropIfExists(string $table): void
    {
        $this->execute(
            $this->createBlueprint($table, fn(Blueprint $blueprint) => $blueprint->dropIfExists())
        );
    }

    /**
     * Drops all the tables in the database.
     *
     * @param bool  $vacuum     Whether to vacuum the database after dropping the tables. Defaults to `false`.
     *
     * @return void
     */
    public function dropAllTables(bool $vacuum = false): void
    {
        $this->execute(
            $this->createBlueprint(fn(Blueprint $blueprint) => $blueprint->dropAllTables($vacuum))
        );
    }

    /**
     * Executes the given blueprint on the database connection.
     *
     * @param Blueprint     $blueprint
     *
     * @return void
     */
    protected function execute(Blueprint $blueprint): void
    {
        if ($this->pretending) {
            $this->dump($blueprint);
            return;
        }

        $statements = $blueprint->build();

        $this->connection->transaction(function () use ($statements) {
            foreach ($statements as $statement) {
                $this->connection->statement($statement);
            }
        });
    }

    /**
     * Dumps the given blueprint into a list of SQL commands.
     *
     * @param Blueprint     $blueprint
     *
     * @return void
     */
    protected function dump(Blueprint $blueprint): void
    {
        $statements = $blueprint->build();

        foreach ($statements as $statement) {
            Log::notice($statement);
        }
    }

    /**
     * Gets a new schema with the given database connection.
     *
     * @param string|DatabaseConnection     $connection
     *
     * @return Schema
     */
    public static function connection(string|DatabaseConnection $connection): Schema
    {
        if (is_string($connection)) {
            $connection = resolve(ConnectionManager::class)->connection($connection);
        }

        return new Schema($connection);
    }
}
