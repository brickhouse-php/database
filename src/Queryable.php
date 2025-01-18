<?php

namespace Brickhouse\Database;

interface Queryable
{
    /**
     * Run an SQL statement and get the returned rows as an array.
     *
     * @param string                    $query      The SQL query to execute.
     * @param array<int|string,mixed>   $bindings   Optional bindings to apply to the query.
     *
     * @return array<int,array<string,mixed>>
     */
    public function select(string $query, array $bindings = []): array;

    /**
     * Run an SQL statement and get the first returned row as an associated array.
     *
     * @param string                    $query      The SQL query to execute.
     * @param array<int|string,mixed>   $bindings   Optional bindings to apply to the query.
     *
     * @return array<string,mixed>|false
     */
    public function selectSingle(string $query, array $bindings = []): array|false;

    /**
     * Run an SQL statement and return whether it succeeded.
     *
     * @param string                    $query      The SQL query to execute.
     * @param array<int|string,mixed>   $bindings   Optional bindings to apply to the query.
     *
     * @return bool
     */
    public function statement(string $query, array $bindings = []): bool;

    /**
     * Run an SQL statement and return the number of affected rows.
     *
     * @param string                    $query      The SQL query to execute.
     * @param array<int|string,mixed>   $bindings   Optional bindings to apply to the query.
     *
     * @return int
     */
    public function affectingStatement(string $query, array $bindings = []): int;
}
