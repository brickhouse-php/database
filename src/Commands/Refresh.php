<?php

namespace Brickhouse\Database\Commands;

use Brickhouse\Console\Command;
use Brickhouse\Database\Events;
use Brickhouse\Database\Migrations\Migrator;

class Refresh extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'migrate:refresh';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Rolls back all migrations, then re-applies them.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        listen(Events\MigrationsStarted::class, function () {
            $this->notice("Migrations started." . PHP_EOL);
        });

        listen(Events\MigrationsFinished::class, function () {
            $this->notice("Migrations finished." . PHP_EOL);
        });

        listen(Events\RollbacksStarted::class, function () {
            $this->notice("Rollbacks started." . PHP_EOL);
        });

        listen(Events\RollbacksFinished::class, function () {
            $this->notice("Rollbacks finished." . PHP_EOL);
        });

        listen(Events\NoPendingMigrations::class, function () {
            $this->notice("No pending migrations.");
        });

        listen(Events\NoPendingRollbacks::class, function () {
            $this->notice("No pending rollbacks.");
        });

        listen(Events\MigrationStarted::class, function (Events\MigrationStarted $event) {
            $this->write(str_pad("Applying <fg=gray>{$event->name}</>", 96, "."));
        });

        listen(Events\MigrationFinished::class, function () {
            $this->writeln("<fg=green>DONE</>");
        });

        listen(Events\RollbackStarted::class, function (Events\RollbackStarted $event) {
            $this->write(str_pad("Rolling back <fg=gray>{$event->name}</>", 96, "."));
        });

        listen(Events\RollbackFinished::class, function () {
            $this->writeln("<fg=green>DONE</>");
        });

        $migrator = resolve(Migrator::class);

        $migrator->reset();
        $migrator->applyPendingMigrations();

        return 0;
    }
}
