<?php

namespace Brickhouse\Database;

final readonly class Query
{
    /**
     * @param string                    $sql        The SQL query.
     * @param array<int|string,mixed>   $bindings   Optional bindings to apply to the query.
     */
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings = [],
        public readonly int $fetch = \PDO::FETCH_ASSOC
    ) {
    }
}
