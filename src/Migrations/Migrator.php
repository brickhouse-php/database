<?php

namespace Brickhouse\Database\Migrations;

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\Events;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;
use Carbon\Carbon;
use Symfony\Component\Finder\Finder;

final class Migrator
{
    public const string MIGRATION_TABLE_NAME = "__migrations";

    /**
     * Gets whether the migrator should pretend to apply migrations.
     */
    public private(set) bool $pretending = false;

    public function __construct(
        private readonly ConnectionManager $connectionManager,
    ) {
        $this->createMigrationsTable();
    }

    /**
     * Defines whether the migrator should pretend to apply migrations.
     *
     * @param bool $pretend
     *
     * @return void
     */
    public function pretend(bool $pretend = true): void
    {
        $this->pretending = $pretend;
    }

    /**
     * Gets whether the given migration has been applied.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isMigrationApplied(string $name): bool
    {
        $migrations = $this->migrations();
        if (!isset($migrations[$name])) {
            return false;
        }

        $migration = $migrations[$name];

        $connection = $this->connectionManager->connection($migration->connection);
        $builder = new QueryBuilder($connection);

        $row = $builder
            ->from(self::MIGRATION_TABLE_NAME)
            ->select('name')
            ->where('name', $name)
            ->first();

        return $row !== null;
    }

    /**
     * Applies all the pending migrations, which have not yet been applied to the database.
     *
     * @return void
     */
    public function applyPendingMigrations(): void
    {
        event(new Events\MigrationsStarted());

        $migrations = $this->pendingMigrations();

        if (empty($migrations)) {
            event(new Events\NoPendingMigrations());
            return;
        }

        foreach ($migrations as $name => $migration) {
            $this->applyMigration($name, $migration);
        }

        event(new Events\MigrationsFinished());
    }

    /**
     * Rolls back all application migrations.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->rollback(PHP_INT_MAX);
    }

    /**
     * Rolls back `$step` amount of migrations, ordered by their application timestamp.
     *
     * @return void
     */
    public function rollback(int $step = 1): void
    {
        event(new Events\RollbacksStarted());

        $migrations = $this->migrations();
        $migrations = array_reverse($migrations, preserve_keys: true);

        if (empty($migrations)) {
            event(new Events\NoPendingRollbacks());
            return;
        }

        $count = 0;

        foreach ($migrations as $name => $migration) {
            if (!$this->isMigrationApplied($name)) {
                continue;
            }

            $this->revertMigration($name, $migration);

            if ($count++ >= $step) {
                break;
            }
        }

        event(new Events\RollbacksFinished());
    }

    /**
     * Gets all the migrations found in the application.
     *
     * @return array<string,Migration>
     */
    private function migrations(): array
    {
        $finder = new Finder()
            ->in(migrations_path())
            ->files()
            ->name('*.php')
            ->ignoreVCS(false)
            ->ignoreUnreadableDirs();

        $migrations = [];

        foreach ($finder as $file) {
            $migrationName = $file->getFilenameWithoutExtension();
            $migration = require $file->getRealPath();

            if (!$migration instanceof Migration) {
                continue;
            }

            $migrations[$migrationName] = $migration;
        }

        // Sort the migrations by their name.
        ksort($migrations, SORT_STRING);

        return $migrations;
    }

    /**
     * Gets all the migrations which have been applied in the database.
     *
     * @return array<string,Migration>
     */
    private function pendingMigrations(): array
    {
        $pending = [];

        foreach ($this->migrations() as $name => $migration) {
            $connection = $this->connectionManager->connection($migration->connection);
            $builder = new QueryBuilder($connection);

            $applied = $builder
                ->from(self::MIGRATION_TABLE_NAME)
                ->select('name')
                ->where('name', $name)
                ->get();

            if (empty($applied->items())) {
                $pending[$name] = $migration;
            }
        }

        return $pending;
    }

    /**
     * Creates the table which stores information about applied migrations.
     *
     * @return void
     */
    private function createMigrationsTable(): void
    {
        new Schema()->createIfNotExists(self::MIGRATION_TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->text("name");
            $table->timestampTz("applied_at");
        });
    }

    /**
     * Apply the given migration to the database.
     *
     * @return void
     */
    public function applyMigration(string $name, Migration $migration): void
    {
        event(new Events\MigrationStarted($name, $migration));

        $schema = new Schema($migration->connection);
        $builder = new QueryBuilder($schema->connection);

        $schema->pretend($this->pretending);

        // Apply the migration
        $migration->up($schema);

        if (!$this->pretending) {
            // Add the migration name to the migrations table.
            $builder
                ->from(self::MIGRATION_TABLE_NAME)
                ->insert([
                    'name' => $name,
                    'applied_at' => Carbon::now()->toDateTimeString()
                ]);
        }

        event(new Events\MigrationFinished($name, $migration));
    }

    /**
     * Revert the given migration to the database.
     *
     * @return void
     */
    public function revertMigration(string $name, Migration $migration): void
    {
        event(new Events\RollbackStarted($name, $migration));

        $schema = new Schema($migration->connection);
        $builder = new QueryBuilder($schema->connection);

        $schema->pretend($this->pretending);

        // Revert the migration
        $migration->down($schema);

        if (!$this->pretending) {
            // Add the migration name to the migrations table.
            $builder
                ->from(self::MIGRATION_TABLE_NAME)
                ->where('name', $name)
                ->delete();
        }

        event(new Events\RollbackFinished($name, $migration));
    }
}
