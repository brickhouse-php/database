<?php

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\Postgres\PostgresGrammar;

describe('Grammar::compileSelect()', function () {
    it('compiles valid SELECT statement', function () {
        $query = new QueryBuilder($this->connection());
        $query->from('users')->select('id');

        $statement = new PostgresGrammar()->compileSelect($query);

        expect($statement)->toBe("SELECT id FROM users");
    });
})->group('postgres');
