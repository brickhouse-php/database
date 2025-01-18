<?php

namespace Brickhouse\Database;

interface DatabaseConnectionString
{
    /**
     * Gets the database-specific connection string for the provider.
     *
     * @var string
     */
    public string $connectionString { get; }

    /**
     * Gets the username to connect to the database provider as.
     *
     * @var null|string
     */
    public null|string $username { get; }

    /**
     * Gets the password associated with the username.
     *
     * @var null|string
     */
    public null|string $password { get; }

    /**
     * Connect to the database.
     *
     * @return DatabaseConnection
     */
    public function connect(): DatabaseConnection;
}
