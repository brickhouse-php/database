<?php

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\Sqlite\SqliteConnection;
use Brickhouse\Database\Sqlite\SqliteGrammar;

describe('Grammar::compileSelect()', function () {
    it('compiles valid SELECT statement', function () {
        $query = new QueryBuilder(SqliteConnection::inMemory());
        $query->from('users')->select('id');

        $statement = new SqliteGrammar()->compileSelect($query);

        expect($statement)->toBe("SELECT id FROM users");
    });
})->group('database', 'sqlite');
