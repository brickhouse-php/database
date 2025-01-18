<?php

namespace Brickhouse\Database\Commands;

use Brickhouse\Console\Command;
use Brickhouse\Database\Events;
use Brickhouse\Database\Migrations\Migrator;

class Reset extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'migrate:reset';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Rolls back all application migrations.';

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
        $migrator->reset();

        return 0;
    }
}
