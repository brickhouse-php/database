<?php

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\Sqlite\SqliteConnection;

beforeEach(function () {
    $this->builder = new QueryBuilder(SqliteConnection::inMemory());
});

describe('QueryBuilder', function () {
    it('selects all columns by default', function () {
        $builder = $this->builder
            ->from('users');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users");
    });

    it('can select individual columns', function () {
        $builder = $this->builder
            ->from('users')
            ->select(['name', 'username']);

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT name, username FROM users");
    });

    it('can alias columns', function () {
        $builder = $this->builder
            ->from('users')
            ->select('name AS username');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT name AS username FROM users");
    });

    it('adds parameter binding', function () {
        $builder = $this->builder
            ->from('users')
            ->where('type', 'admin');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users WHERE type = ?");
    });

    it('applies WHERE conditions', function () {
        $builder = $this->builder
            ->from('users')
            ->where('name', '=', 'maxnatamo');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users WHERE name = ?");
    });

    it('applies WHERE IN conditions', function () {
        $builder = $this->builder
            ->from('users')
            ->whereIn('name', ['maxnatamo', 'ghost']);

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users WHERE name IN (?, ?)");
    });

    it('applies offsets', function () {
        $builder = $this->builder
            ->from('users')
            ->skip(1);

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users OFFSET 1");
    });

    it('applies limits', function () {
        $builder = $this->builder
            ->from('users')
            ->take(1);

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users LIMIT 1");
    });

    it('applies offset and limits', function () {
        $builder = $this->builder
            ->from('users')
            ->skip(2)
            ->take(1);

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users LIMIT 1 OFFSET 2");
    });

    it('applies inner joins', function () {
        $builder = $this->builder
            ->from('users')
            ->join('admins', 'users.id', '=', 'admins.user');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users INNER JOIN admins ON users.id = admins.user");
    });

    it('applies left joins', function () {
        $builder = $this->builder
            ->from('users')
            ->leftJoin('admins', 'users.id', '=', 'admins.user');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users LEFT JOIN admins ON users.id = admins.user");
    });

    it('applies right joins', function () {
        $builder = $this->builder
            ->from('users')
            ->rightJoin('admins', 'users.id', '=', 'admins.user');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users RIGHT JOIN admins ON users.id = admins.user");
    });

    it('applies cross joins', function () {
        $builder = $this->builder
            ->from('users')
            ->crossJoin('admins', 'users.id', '=', 'admins.user');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT * FROM users CROSS JOIN admins ON users.id = admins.user");
    });

    it('selects raw SELECT statements', function () {
        $builder = $this->builder
            ->from('users')
            ->selectRaw('COUNT(id) AS user_count');

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT COUNT(id) AS user_count FROM users");
    });

    it('binds parameters in raw SELECT statements', function () {
        $builder = $this->builder
            ->from('products')
            ->selectRaw('price * ? AS price_taxed', [1.25]);

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT price * ? AS price_taxed FROM products");
    });

    it('selects distinct values', function () {
        $builder = $this->builder
            ->from('users')
            ->select("name")
            ->distinct();

        $query = $builder->grammar->compileSelect($builder);

        expect($query)->toBe("SELECT DISTINCT name FROM users");
    });

    it('inserts values into columns', function () {
        $builder = $this->builder->from('users');

        $query = $builder->grammar->compileInsert($builder, [
            ['name' => 'Max Trier', 'username' => 'maxnatamo', 'email' => 'me@maxtrier.dk'],
        ]);

        expect($query)->toBe("INSERT INTO users (name, username, email) VALUES (?, ?, ?)");
    });

    it('updates values in table', function () {
        $builder = $this->builder->from('users');

        $query = $builder->grammar->compileUpdate($builder, ['type' => 'admin']);

        expect($query)->toBe("UPDATE users SET type = ?");
    });

    it('updates conditioned values in table', function () {
        $builder = $this->builder
            ->from('users')
            ->where('username', 'maxnatamo');

        $query = $builder->grammar->compileUpdate($builder, ['type' => 'admin']);

        expect($query)->toBe("UPDATE users SET type = ? WHERE username = ?");
    });

    it('deletes values from table', function () {
        $builder = $this->builder->from('users');

        $query = $builder->grammar->compileDelete($builder);

        expect($query)->toBe("DELETE FROM users");
    });

    it('deletes conditioned values from table', function () {
        $builder = $this->builder
            ->from('users')
            ->where('username', 'maxnatamo');

        $query = $builder->grammar->compileDelete($builder);

        expect($query)->toBe("DELETE FROM users WHERE username = ?");
    });
})->group('database', 'sqlite');
