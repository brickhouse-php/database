<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Brickhouse\Database\Sqlite\SqliteConnectionString;

pest()->extend(\Brickhouse\Database\Tests\TestCase::class);

beforeEach(function () use (&$connection) {
    $config = new DatabaseConfig([
        SqliteConnectionString::inMemory(),
    ]);

    Application::current()->instance(ConnectionManager::class, new ConnectionManager($config));

    $schema = new Schema();

    $schema->create('posts', function (Blueprint $table) {
        $table->id();
        $table->text('title');
        $table->text('body');
        $table->belongsTo(Author::class)->nullable();
    });

    $schema->create('authors', function (Blueprint $table) {
        $table->id();
        $table->text('name');
    });
});

afterEach(function () {
    $schema = new Schema();

    $schema->drop('authors');
    $schema->drop('posts');
});

describe('QueryBuilder::clone', function () {
    it('creates a new instance with the same connection', function () {
        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        expect($queryBuilder->clone())->toEqual($queryBuilder);
    });
})->group('database');

describe('QueryBuilder::where', function () {
    it('throws exception given invalid operator', function () {
        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder->where('column', 'OPERATOR', 'value');
    })->throws(\RuntimeException::class);
})->group('database');

describe('QueryBuilder::get', function () {
    it('overrides previously selected columns given columns', function () {
        Author::create(['name' => 'Onion News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $rows = $queryBuilder
            ->from('authors')
            ->select('*')
            ->get('id');

        expect($rows)->toMatchArray([
            ['id' => 1]
        ]);
    });
})->group('database');

describe('QueryBuilder::value', function () {
    it('selects only a single column as a flattened array', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Rokoko Posten']);
        Author::create(['name' => 'The Hard Times']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $rows = $queryBuilder
            ->from('authors')
            ->value('id');

        expect($rows->toArray())->toMatchArray([1, 2, 3]);
    });
})->group('database');

describe('QueryBuilder::first', function () {
    it('selects only the first record', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->where('name', 'LIKE', '%News')
            ->first();

        expect($row)->toMatchArray(['id' => 1, 'name' => 'Onion News']);
    });

    it('overrides columns if given', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->where('name', 'LIKE', '%News')
            ->first('name');

        expect($row)->toMatchArray(['name' => 'Onion News']);
    });

    it('returns null given no match', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->where('name', '=', 'Rokoko Posten')
            ->first();

        expect($row)->toBeNull();
    });
})->group('database');

describe('QueryBuilder::firstOrFail', function () {

    it('selects only the first record', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->where('name', 'LIKE', '%News')
            ->firstOrFail();

        expect($row)->toMatchArray(['id' => 1, 'name' => 'Onion News']);
    });

    it('throws exception given no match', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder
            ->from('authors')
            ->where('name', '=', 'Rokoko Posten')
            ->firstOrFail();
    })->throws(\RuntimeException::class);
})->group('database');

describe('QueryBuilder::firstOr', function () {
    it('selects only the first record', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->where('name', 'LIKE', '%News')
            ->firstOr(fn() => 'empty');

        expect($row)->toMatchArray(['id' => 1, 'name' => 'Onion News']);
    });

    it('returns fallback given no match', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->where('name', '=', 'Rokoko Posten')
            ->firstOr(fn() => 'empty');

        expect($row)->toBe('empty');
    });
})->group('database');

describe('QueryBuilder::pluck', function () {
    it('selects only the given columns', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->pluck('name');

        expect($row->toArray())->toMatchArray([
            0 => 'Onion News',
            1 => 'Weekly World News',
        ]);
    });

    it('keys the collection given two arguments', function () {
        Author::create(['name' => 'Onion News']);
        Author::create(['name' => 'Weekly World News']);

        $connection = resolve(ConnectionManager::class)->connection();
        $queryBuilder = new QueryBuilder($connection);

        $row = $queryBuilder
            ->from('authors')
            ->pluck('id', 'name');

        expect($row->toArray())->toMatchArray([
            1 => 'Onion News',
            2 => 'Weekly World News',
        ]);
    });
})->group('database');
