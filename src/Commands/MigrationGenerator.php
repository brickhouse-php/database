<?php

namespace Brickhouse\Database\Commands;

use Brickhouse\Console\Attributes\Argument;
use Brickhouse\Console\Attributes\Option;
use Brickhouse\Console\GeneratorCommand;
use Brickhouse\Console\InputOption;
use Brickhouse\Support\StringHelper;

class MigrationGenerator extends GeneratorCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'generate:migration';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Scaffolds a new database migration.';

    /**
     * Defines the name of the generated migration.
     *
     * @var string
     */
    #[Argument("name", "Specifies the name of the migration", InputOption::REQUIRED)]
    public string $migrationName = '';

    /**
     * Defines the name of the model for the migration.
     *
     * @var null|string
     */
    #[Option("model", null, "Defines the name of the model for the migration.", InputOption::REQUIRED)]
    public null|string $model = null;

    /**
     * @inheritDoc
     */
    protected function sourceRoot(): string
    {
        return __DIR__ . '/../Stubs/';
    }

    /**
     * @inheritDoc
     */
    public function handle(): int
    {
        $stub = $this->model !== null
            ? 'Migration.stub.php'
            : 'Migration.base.stub.php';

        $this->copy(
            $stub,
            $this->getPath(),
            [
                'tableName' => StringHelper::from($this->model)->pluralize()->lower(),
            ]
        );

        return 0;
    }

    /**
     * Defines the path of where to place the migration.
     *
     * @return void
     */
    protected function getPath(): string
    {
        $filename = pathinfo($this->migrationName, PATHINFO_FILENAME);
        $timestampPrefix = \Carbon\Carbon::now()->format('Ymd_His');

        if ($this->model === null) {
            $name = "{$timestampPrefix}_{$filename}";
        } else {
            $modelName = StringHelper::from($this->model)
                ->pluralize()
                ->lower()
                ->__toString();

            $name = "{$timestampPrefix}_add_{$modelName}_table";
        }

        return path('resources', 'migrations', $name . '.php');
    }
}
