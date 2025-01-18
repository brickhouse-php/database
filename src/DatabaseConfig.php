<?php

namespace Brickhouse\Database;

use Brickhouse\Config\Config;

final class DatabaseConfig extends Config
{
    /**
     * Creates a new instance of `DatabaseConfig`.
     *
     * @param array<int|string,DatabaseConnectionString>    $connections    Defines all the database connection strings for the application.
     */
    public function __construct(
        public readonly array $connections = [],
    ) {}
}
