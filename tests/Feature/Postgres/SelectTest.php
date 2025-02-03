<?php

use Brickhouse\Database\Postgres\PostgresConnection;

describe('DatabaseConnection::select()', function () {
    it('returns array of returned rows', function () {
        /** @var PostgresConnection $database */
        $database = $this->connection();

        $rows = $database->select("SELECT * FROM (VALUES (1), (2)) AS t(column1)");

        expect($rows)->toEqual([
            ["column1" => 1],
            ["column1" => 2]
        ]);
    });
})->group('postgres');
