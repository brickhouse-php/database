<?php

namespace Brickhouse\Database\Commands;

use Brickhouse\Console\Attributes\Option;
use Brickhouse\Console\Command;
use Brickhouse\Console\InputOption;
use Brickhouse\Database\Events;
use Brickhouse\Database\Migrations\Migrator;

class Migrate extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'migrate';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Apply all pending migrations to the database.';

    #[Option("pretend", description: 'Pretends to apply the pending migrations and print the SQL to console.', input: InputOption::NEGATABLE)]
    public bool $pretend = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        listen(Events\NoPendingMigrations::class, function () {
            $this->notice("No pending migrations.");
        });

        if (!$this->pretend) {
            listen(Events\MigrationStarted::class, function (Events\MigrationStarted $event) {
                $this->write(str_pad("Applying <fg=gray>{$event->name}</>", 96, "."));
            });

            listen(Events\MigrationFinished::class, function () {
                $this->writeln("<fg=green>DONE</>");
            });
        }

        $migrator = resolve(Migrator::class);
        $migrator->pretend($this->pretend);
        $migrator->applyPendingMigrations();

        return 0;
    }
}
