<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Brickhouse\Database\Sqlite\SqliteConnectionString;
use Brickhouse\Database\Transposer\Exceptions\UnresolvableHasOneException;

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

    $schema->create('cursed_suppliers', function (Blueprint $table) {
        $table->id();
        $table->text('name');
    });

    $schema->create('cursed_accounts', function (Blueprint $table) {
        $table->id();
        $table->belongsTo(CursedSupplier::class);
        $table->text('account_number');
    });
});

afterEach(function () {
    $schema = new Schema();

    $schema->drop('cursed_accounts');
    $schema->drop('cursed_suppliers');
    $schema->drop('accounts');
    $schema->drop('suppliers');
    $schema->drop('authors');
    $schema->drop('posts');
});

describe('Relations', function () {
    it('applies child relations when saving modal (belongs-to)', function () {
        [$author] = Author::query()->builder->insert([['name' => 'The Onion']]);
        Post::query()->builder->insert([
            ['title' => 'Bowling Union Strikes', 'body' => '', 'author_id' => $author['id']]
        ]);

        $author = Author::query()->with('posts', 'posts.author')->find($author['id']);

        expect(isset($author->posts[0]->author))->toBeTrue();
        expect($author->posts[0]->author->id)->toBe(1);
        expect($author->posts[0]->author->name)->toBe('The Onion');
    });

    it('applies child relations when saving modal (has-many)', function () {
        [$author] = Author::query()->builder->insert([['name' => 'The Onion']]);
        [$post] = Post::query()->builder->insert([
            ['title' => 'Bowling Union Strikes', 'body' => '', 'author_id' => $author['id']]
        ]);

        $post = Post::query()->with('author', 'author.posts')->find($post['id']);

        expect(isset($post->author->posts))->toBeTrue();
        expect($post->author->posts[0]->id)->toBe(1);
        expect($post->author->posts[0]->title)->toBe('Bowling Union Strikes');
    });

    it('retrieves model without loading relation, if not given', function () {
        Author::create([
            'name' => 'The Onion',
            'posts' => [
                Post::new(['title' => 'Bowling Union Strikes', 'body' => ''])
            ]
        ]);

        $authors = Author::all()->toArray();

        expect($authors)->toHaveCount(1);
        expect($authors[0]->name)->toBe('The Onion');
        expect(isset($authors[0]->posts))->toBeFalse();
    });

    it('retrieves model with has-many relation if given', function () {
        Author::create([
            'name' => 'The Onion',
            'posts' => [
                Post::new(['title' => 'Bowling Union Strikes', 'body' => ''])
            ]
        ]);

        $authors = Author::with('posts')->all()->toArray();

        expect($authors)->toHaveCount(1);
        expect($authors[0]->name)->toBe('The Onion');
        expect($authors[0]->posts)->toHaveCount(1)->sequence(
            fn($post) => $post->title->toBe('Bowling Union Strikes')
        );
    });

    it('deletes previous relation when destroying model with `destroyDependent = true` relation', function () {
        $supplier = Supplier::create([
            'name' => 'Example Ltd.',
            'account' => Account::new(['account_number' => '01'])
        ]);

        expect($supplier->account->account_number)->toBe('01');

        $supplier->account = Account::new(['account_number' => '02']);
        $supplier->save();

        expect(Account::all()->toArray())->toHaveCount(1)->sequence(
            fn($account) => $account->account_number->toBe('02')
        );
    });

    it('throws exception when destroying model with `destroyDependent = false` relation', function () {
        $supplier = CursedSupplier::create([
            'name' => 'Example Ltd.',
            'account' => CursedAccount::new(['account_number' => '01'])
        ]);

        expect($supplier->account->account_number)->toBe('01');

        $supplier->account = CursedAccount::new(['account_number' => '02']);
        $supplier->save();

        CursedSupplier::with('account')->find(1);
    })->throws(UnresolvableHasOneException::class);
})->group('database', 'model');

describe('Model::load', function () {
    it('skips loading relation given no load call', function () {
        Author::create([
            'name' => 'The Onion',
            'posts' => [
                Post::new(['title' => 'Bowling Union Strikes', 'body' => ''])
            ]
        ]);

        $author = Author::find(1);

        expect($author->name)->toBe('The Onion');
        expect(isset($author->posts))->toBeFalse();
    });

    it('retrieves relation given has-many relation', function () {
        Author::create([
            'name' => 'The Onion',
            'posts' => [
                Post::new(['title' => 'Bowling Union Strikes', 'body' => ''])
            ]
        ]);

        $author = Author::find(1)->load('posts');

        expect($author->name)->toBe('The Onion');
        expect($author->posts)->toHaveCount(1)->sequence(
            fn($post) => $post->title->toBe('Bowling Union Strikes')
        );
    });

    it('retrieves relation given nested relations', function () {
        Author::create([
            'name' => 'The Onion',
            'posts' => [
                Post::new(['title' => 'Bowling Union Strikes', 'body' => ''])
            ]
        ]);

        $author = Author::find(1)->load('posts', 'posts.author');

        expect($author->name)->toBe('The Onion');
        expect($author->posts)->toHaveCount(1)->sequence(
            function ($post) {
                $post->title->toBe('Bowling Union Strikes');
                $post->author->name->toBe('The Onion');
            }
        );
    });
})->group('database', 'model');
