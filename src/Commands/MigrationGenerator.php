<?php

namespace Brickhouse\Database\Commands;

use Brickhouse\Console\Attributes\Option;
use Brickhouse\Console\GeneratorCommand;
use Brickhouse\Console\InputOption;
use Brickhouse\Support\StringHelper;

class MigrationGenerator extends GeneratorCommand
{
    /**
     * The type of the class generated.
     *
     * @var string
     */
    public string $type = 'Migration';

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
     * Defines the name of the model for the migration.
     *
     * @var null|string
     */
    #[Option("model", null, "Defines the name of the model for the migration.", InputOption::REQUIRED)]
    public null|string $model = null;

    public function stub(): string
    {
        if ($this->model !== null) {
            return __DIR__ . '/../Stubs/Migration.stub.php';
        }

        return __DIR__ . '/../Stubs/Migration.base.stub.php';
    }

    protected function getPath(string $name): string
    {
        $filename = pathinfo($name, PATHINFO_FILENAME);
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

        return migrations_path($name . '.php');
    }

    protected function defaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace . 'Migrations';
    }

    /**
     * Builds the content of the stub.
     *
     * @return string
     */
    protected function buildStub(string $path, string $name): string
    {
        $content = parent::buildStub($path, $name);

        if ($this->model === null) {
            return $content;
        }

        return str_replace(
            ['TableName'],
            [StringHelper::from($this->model)->pluralize()->lower()],
            $content
        );
    }
}
