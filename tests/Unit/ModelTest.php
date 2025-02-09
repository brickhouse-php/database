<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Brickhouse\Database\Sqlite\SqliteConnectionString;
use Brickhouse\Database\Transposer\Ignore;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Relations\BelongsTo;
use Brickhouse\Database\Transposer\Relations\HasMany;
use Brickhouse\Database\Transposer\Relations\HasOne;

pest()->extend(\Brickhouse\Database\Tests\TestCase::class);

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

describe('Model::getProperties()', function () {
    it('returns all public properties on model class', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });

    it('ignores properties with Ignore attribute', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';

            #[Ignore]
            public int $state = 1;
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });

    it('ignores hooked attributes', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';

            public int $state { get => 1; }
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });

    it('ignores readonly attributes', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';

            public readonly int $state;
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });

    it('ignores static attributes', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';

            public static int $state;
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });

    it('ignores non-public attributes', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';

            protected int $state;
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });

    it('ignores attributes with asymmetric visiblity', function () {
        $model = new class extends Model
        {
            public string $name = 'Test';

            public protected(set) int $state;
        };

        expect($model->getProperties())->toMatchArray([
            'name' => 'Test'
        ]);
    });
})->group('database');

describe('Model::getModelRelations()', function () {
    it('returns attribute with HasOne attribute', function () {
        $model = new class extends Model
        {
            #[HasOne(Model::class)]
            public Model $parent;
        };

        expect($model->getModelRelations())->toMatchArray([
            'parent' => new HasOne(Model::class, null, null)
        ]);
    });

    it('returns attribute with HasMany attribute', function () {
        $model = new class extends Model
        {
            #[HasMany(Model::class)]
            public Model $parent;
        };

        expect($model->getModelRelations())->toMatchArray([
            'parent' => new HasMany(Model::class, null, null)
        ]);
    });

    it('returns attribute with BelongsTo attribute', function () {
        $model = new class extends Model
        {
            #[BelongsTo(Model::class)]
            public Model $parent;
        };

        expect($model->getModelRelations())->toMatchArray([
            'parent' => new BelongsTo(Model::class, null, null)
        ]);
    });
})->group('database');

describe('Model::isModelRelational()', function () {
    it('returns true given relational property name', function () {
        $model = new class extends Model
        {
            #[HasOne(Model::class)]
            public Model $parent;
        };

        expect($model->isModelRelation('parent'))->toBeTrue();
    });

    it('returns false given non-relational property name', function () {
        $model = new class extends Model
        {
            public string $parent;
        };

        expect($model->isModelRelation('parent'))->toBeFalse();
    });

    it('returns false given non-existing property name', function () {
        $model = new class extends Model {};

        expect($model->isModelRelation('parent'))->toBeFalse();
    });
})->group('database');
