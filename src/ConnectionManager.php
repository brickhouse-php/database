<?php

namespace Brickhouse\Database;

use Brickhouse\Database\Exceptions\InvalidConnectionException;

class ConnectionManager
{
    /**
     * Gets a cached array of all connected database connections.
     *
     * @var array<int|string,DatabaseConnection>
     */
    protected array $connections = [];

    public function __construct(
        protected readonly DatabaseConfig $config,
    ) {}

    /**
     * Gets an open database connection to the connection with the given name.
     * If `$name` is `null`, returns the default database connection.
     *
     * @param null|string   $name
     *
     * @return DatabaseConnection
     */
    public function connection(null|string $name = null): DatabaseConnection
    {
        if (isset($this->connections[$name ?? 0])) {
            return $this->connections[$name ?? 0];
        }

        if ($name !== null && !array_key_exists($name, $this->config->connections)) {
            throw new InvalidConnectionException($name);
        }

        if ($name === null && !array_key_exists(0, $this->config->connections)) {
            throw new InvalidConnectionException($name);
        }

        $connectionString = $this->config->connections[$name ?? 0];
        $connection = $this->connections[$name ?? 0] = $connectionString->connect();

        return $connection;
    }
}
