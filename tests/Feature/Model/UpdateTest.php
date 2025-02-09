<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Brickhouse\Database\Sqlite\SqliteConnectionString;

beforeEach(function () {
    $config = new DatabaseConfig([
        SqliteConnectionString::inMemory(),
    ]);

    $manager = new ConnectionManager($config);

    Application::current()->instance($manager::class, $manager);

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

    $schema->create('suppliers', function (Blueprint $table) {
        $table->id();
        $table->text('name');
    });

    $schema->create('accounts', function (Blueprint $table) {
        $table->id();
        $table->belongsTo(Supplier::class);
        $table->text('account_number');
    });
});

afterEach(function () {
    $schema = new Schema();

    $schema->drop('accounts');
    $schema->drop('suppliers');
    $schema->drop('authors');
    $schema->drop('posts');
});

describe('Model update', function () {
    it('updates model correctly', function () {
        $post = Post::create(['title' => 'Post Title', 'body' => 'Some Content']);

        expect(Post::query()->first()->title)->toBe('Post Title');

        $post->title = 'Some Other Title';
        $post->save();

        expect(Post::query()->first()->title)->toBe('Some Other Title');
    });

    it('skips update if nothing is changed', function () {
        $connection = resolve(ConnectionManager::class)->connection();
        $post = Post::create(['title' => 'Post Title', 'body' => 'Some Content']);

        $queryLog = $connection->withQueryLog(fn() => $post->save());

        expect($queryLog)->toBeEmpty();
    });

    it('updates using only dirty attributes', function () {
        $connection = resolve(ConnectionManager::class)->connection();

        $post = Post::create(['title' => 'Post Title', 'body' => 'Some Content']);
        $post->title = 'Some Other Title';

        $connection->enableQueryLogging();

        $post->save();

        $connection->disableQueryLogging();

        expect($connection->getQueryLog()[0])->toMatchArray([
            'query' => 'UPDATE posts SET title = ? WHERE id = ?',
            'bindings' => ['Some Other Title', 1]
        ]);
    });

    it('updates related models (has-one)', function () {
        $supplier = Supplier::create([
            'name' => 'Example Ltd.',
            'account' => Account::new(['account_number' => '01010101'])
        ]);

        $result = Supplier::with('account')->find($supplier->id);
        expect($result->name)->toBe('Example Ltd.');
        expect($result->account->account_number)->toBe('01010101');

        $supplier->account = Account::new(['account_number' => '12121212']);
        $supplier->save();

        $result = Supplier::with('account')->find($supplier->id);

        expect($result->name)->toBe('Example Ltd.');
        expect($result->account->account_number)->toBe('12121212');

        expect(Account::all()->toArray())->toHaveCount(1)->sequence(
            fn($account) => $account->account_number->toBe('12121212'),
        );
    });

    it('updates related models (has-many)', function () {
        $post = Author::create(['name' => 'Edgar Allan Poe', 'posts' => []]);
        $post->posts[] = Post::new(['title' => 'The Raven', 'body' => 'Now as a blog post!']);
        $post->save();

        expect(Author::all()->toArray())->toHaveCount(1)->sequence(
            fn($author) => $author->name->toBe('Edgar Allan Poe')
        );

        expect(Post::all()->toArray())->toHaveCount(1)->sequence(
            function ($post) {
                $post->title->toBe('The Raven');
                $post->body->toBe('Now as a blog post!');
            }
        );
    });
})->group('database');
