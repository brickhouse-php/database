<?php

namespace Brickhouse\Database\Postgres;

use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\DatabaseConnectionString;

final class PostgresConnectionString implements DatabaseConnectionString
{
    /**
     * @inheritDoc
     */
    public string $connectionString {
        get => sprintf('pgsql:host=%s;port=%d;dbname=%s', $this->host, $this->port, $this->database);
    }

    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        #[\SensitiveParameter] public string $password,
    ) {}

    /**
     * @inheritDoc
     */
    public function connect(): DatabaseConnection
    {
        return new PostgresConnection($this);
    }
}
