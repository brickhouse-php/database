<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Transposer\DatabaseModel;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Relations\HasMany;
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
        $table->integer('author_id')->foreign('authors', 'id')->nullable();
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

describe('Model creation', function () {
    it('can create a new model without saving to database', function () {
        Post::new([
            'title' => 'Post Title',
            'body' => 'Some Content'
        ]);

        expect(Post::all()->toArray())->toBeEmpty();
    });

    it('can create a new model and save it to database', function () {
        $post = Post::create([
            'title' => 'Post Title',
            'body' => 'Some Content'
        ]);

        expect(Post::all()->toArray())
            ->toHaveCount(1)
            ->sequence(
                fn($post) => $post->title->toBe('Post Title')
            );
    });

    it('throws when creating an abstract model', function () {
        AbstractPost::new([
            'title' => 'Post Title',
            'body' => 'Some Content'
        ]);
    })->throws(\RuntimeException::class);

    it('can create a new model with has-many relations', function () {
        Author::create(['name' => 'Some Author']);

        expect(Author::all()->toArray())->toHaveCount(1);
        expect(Post::all()->toArray())->toBeEmpty();
    });

    it('can create a new model with has-many related models', function () {
        Author::create([
            'name' => 'Some Author',
            'posts' => [
                Post::create(['title' => 'Post 1', 'body' => '']),
                Post::create(['title' => 'Post 2', 'body' => '']),
                Post::create(['title' => 'Post 3', 'body' => '']),
            ]
        ]);

        expect(Author::all()->toArray())->toHaveCount(1);
        expect(Post::all()->toArray())->toHaveCount(3);
    });
})->group('database');

class Post implements Model
{
    use DatabaseModel;

    public string $title;
    public string $body;
}

abstract class AbstractPost implements Model
{
    use DatabaseModel;

    public string $title;
    public string $body;
}

class Author implements Model
{
    use DatabaseModel;

    public string $name;

    #[HasMany(Post::class)]
    public array $posts;
}
