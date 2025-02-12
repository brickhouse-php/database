<?php

use Brickhouse\Core\Application;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\DatabaseConfig;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Brickhouse\Database\Sqlite\SqliteConnectionString;
use Brickhouse\Database\Transposer\Exceptions\ModelNormalizationException;
use Brickhouse\Database\Transposer\Model;

beforeEach(function () {
    $config = new DatabaseConfig([
        SqliteConnectionString::inMemory(),
    ]);

    $manager = new ConnectionManager($config);

    Application::current()->instance($manager::class, $manager);

    $schema = new Schema();

    $schema->create('members', function (Blueprint $table) {
        $table->id();
        $table->text('email');
    });
});

afterEach(function () {
    $schema = new Schema();

    $schema->drop('members');
});

describe('Normalization', function () {
    it('normalizes nothing given unimplemented rule array', function () {
        MemberWithoutNormalization::create(['email' => 'EMAIL@EXAMPLE.com']);

        $members = MemberWithoutNormalization::all();

        expect($members->toArray())->toHaveCount(1)->sequence(
            fn($member) => $member->email->toBe('EMAIL@EXAMPLE.com')
        );
    });

    it('normalizes single attribute given single rule', function () {
        MemberWithSingleNormalizeRule::create(['email' => 'EMAIL@EXAMPLE.com']);

        $members = MemberWithSingleNormalizeRule::all();

        expect($members->toArray())->toHaveCount(1)->sequence(
            fn($member) => $member->email->toBe('email@example.com')
        );
    });

    it('normalizes single attribute given multiple rules', function () {
        MemberWithMultipleNormalizeRules::create(['email' => 'EMAIL@EXAMPLE.com']);

        $members = MemberWithMultipleNormalizeRules::all();

        expect($members->toArray())->toHaveCount(1)->sequence(
            fn($member) => $member->email->toBe('email')
        );
    });

    it('throws exception given indexed rule array', function () {
        MemberWithIndexedNormalizeRules::create(['email' => 'EMAIL@EXAMPLE.com']);
    })->throws(ModelNormalizationException::class);

    it('throws exception given rule with invalid attribute name', function () {
        MemberWithInvalidNormalizeRuleAttribute::create(['email' => 'EMAIL@EXAMPLE.com']);
    })->throws(\InvalidArgumentException::class);
})->group('database', 'model');

class MemberWithoutNormalization extends Model
{
    public string $email = '';

    public static function table(): string
    {
        return 'members';
    }
}

class MemberWithSingleNormalizeRule extends Model
{
    public string $email = '';

    public function normalize(): array
    {
        return [
            'email' => strtolower(...)
        ];
    }

    public static function table(): string
    {
        return 'members';
    }
}

class MemberWithMultipleNormalizeRules extends Model
{
    public string $email = '';

    public function normalize(): array
    {
        return [
            'email' => [
                strtolower(...),
                fn($value) => str_replace('@example.com', '', $value),
            ]
        ];
    }

    public static function table(): string
    {
        return 'members';
    }
}

class MemberWithIndexedNormalizeRules extends Model
{
    public string $email = '';

    public function normalize(): array
    {
        return [
            'strtolower'
        ];
    }

    public static function table(): string
    {
        return 'members';
    }
}

class MemberWithInvalidNormalizeRuleAttribute extends Model
{
    public string $email = '';

    public function normalize(): array
    {
        return [
            'name' => 'strtolower'
        ];
    }

    public static function table(): string
    {
        return 'members';
    }
}
