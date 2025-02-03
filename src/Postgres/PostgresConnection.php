<?php

namespace Brickhouse\Database\Postgres;

use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\Grammar;

class PostgresConnection extends DatabaseConnection
{
    /**
     * Gets the internal grammar instance.
     *
     * @var PostgresGrammar
     */
    private readonly PostgresGrammar $internalGrammar;

    /**
     * @inheritDoc
     */
    public Grammar $grammar {
        get => $this->internalGrammar;
    }

    /**
     * Connect to the database using PHP's PDO interface.
     *
     * @param PostgresConnectionString        $connectionString
     */
    public function __construct(
        PostgresConnectionString $connectionString,
    ) {
        parent::__construct($connectionString);

        $this->internalGrammar = new PostgresGrammar;
    }
}
