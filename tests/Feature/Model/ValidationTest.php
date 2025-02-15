<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Relations\HasMany;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Brickhouse\Database\Sqlite\SqliteConnectionString;
use Brickhouse\Database\Transposer\Relations\BelongsTo;
use Brickhouse\Database\Transposer\Relations\HasOne;
use Brickhouse\Validation\Rules\Required;

beforeEach(function () {
    $config = new DatabaseConfig([
        SqliteConnectionString::inMemory(),
    ]);

    $manager = new ConnectionManager($config);

    Application::current()->instance($manager::class, $manager);

    $schema = new Schema();

    $schema->create('users', function (Blueprint $table) {
        $table->id();
        $table->text('name');
        $table->text('email');
        $table->text('team');
    });
});

afterEach(function () {
    $schema = new Schema();

    $schema->drop('users');
});

describe('Model validation', function () {
    it('inserts model given empty validation rules', function () {
        UserWithoutValidationRules::create(['name' => 'Max', 'email' => 'me@maxtrier.dk', 'team' => 'Development']);

        expect(UserWithoutValidationRules::all()->toArray())->toHaveCount(1);
    });

    it('inserts model given passing validation rules', function () {
        UserWithSimpleValidationRule::create(['name' => 'Max', 'email' => 'me@maxtrier.dk', 'team' => 'Development']);

        expect(UserWithSimpleValidationRule::all()->toArray())->toHaveCount(1);
    });

    it('does not insert model given failing validation rules', function () {
        $model = UserWithSimpleValidationRule::create(['name' => '', 'email' => 'me@maxtrier.dk', 'team' => 'Development']);

        expect(UserWithSimpleValidationRule::all()->toArray())->toHaveCount(0);
        expect($model->errors)->toMatchArray([
            'name' => ['Field {attribute} is required.']
        ]);
    });
})->group('database');

class UserWithoutValidationRules extends Model
{
    public string $name;

    public string $email;

    public string $team;

    public function validate(): array
    {
        return [];
    }

    public static function table(): string
    {
        return 'users';
    }
}

class UserWithSimpleValidationRule extends Model
{
    public string $name;

    public string $email;

    public string $team;

    public function validate(): array
    {
        return [
            'name' => [new Required()]
        ];
    }

    public static function table(): string
    {
        return 'users';
    }
}
