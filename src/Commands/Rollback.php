<?php

namespace Brickhouse\Database\Commands;

use Brickhouse\Console\Attributes\Option;
use Brickhouse\Console\Command;
use Brickhouse\Console\InputOption;
use Brickhouse\Database\Events;
use Brickhouse\Database\Migrations\Migrator;

class Rollback extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'migrate:rollback';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Rolls back previous migrations.';

    #[Option("step", description: 'Defines how many migrations to roll back.', input: InputOption::REQUIRED)]
    public int $steps = 1;

    #[Option("pretend", description: 'Pretends to rollback the pending migrations and print the SQL to console.', input: InputOption::NEGATABLE)]
    public bool $pretend = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        listen(Events\NoPendingRollbacks::class, function () {
            $this->notice("No pending rollbacks.");
        });

        listen(Events\RollbackStarted::class, function (Events\RollbackStarted $event) {
            $this->write(str_pad("Rolling back <fg=gray>{$event->name}</>", 96, "."));
        });

        listen(Events\RollbackFinished::class, function () {
            $this->writeln("<fg=green>DONE</>");
        });

        $migrator = resolve(Migrator::class);
        $migrator->pretend($this->pretend);
        $migrator->rollback($this->steps);

        return 0;
    }
}
