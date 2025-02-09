<?php

namespace Brickhouse\Database\Schema;

use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\Grammar;
use Brickhouse\Database\Transposer\Model;

class Blueprint
{
    /**
     * Contains a list of all commands to execute in the blueprint.
     *
     * @var list<Command>
     */
    public private(set) array $commands = [];

    /**
     * Contains a list of all altered columns.
     *
     * @var array<string,Column>
     */
    public protected(set) array $columns = [];

    /**
     * Get the grammar instance of the database connection.
     *
     * @var Grammar
     */
    protected Grammar $grammar { get => $this->connection->grammar; }

    /**
     * Gets whether the blueprint is creating, altering or dropping a table.
     *
     * @var 'create'|'alter'|'drop'
     */
    public protected(set) string $change = 'create';

    public function __construct(
        public readonly string $table,
        public readonly DatabaseConnection $connection,
        protected readonly \Closure $callback,
    ) {}

    /**
     * Creates the table.
     *
     * @return self
     */
    public function create(): self
    {
        $this->change = 'create';

        $this->addCommand('create');
        return $this;
    }

    /**
     * Creates the table, if it doesn't already exist.
     *
     * @return self
     */
    public function createIfNotExists(): self
    {
        $this->change = 'create';

        $this->addCommand('createIfNotExists');
        return $this;
    }

    /**
     * Alters the table.
     *
     * @return self
     */
    public function alter(): self
    {
        $this->change = 'alter';

        $this->addCommand('alter');
        return $this;
    }

    /**
     * Drops the table.
     *
     * @return self
     */
    public function drop(): self
    {
        $this->change = 'drop';

        $this->addCommand('drop');
        return $this;
    }

    /**
     * Drops the table, if it exists.
     *
     * @return self
     */
    public function dropIfExists(): self
    {
        $this->change = 'drop';

        $this->addCommand('dropIfExists');
        return $this;
    }

    /**
     * Drops all tables in the database.
     *
     * @param bool  $vacuum     Whether to vacuum the database after dropping the tables. Defaults to `false`.
     *
     * @return self
     */
    public function dropAllTables(bool $vacuum = false): self
    {
        $this->change = 'drop';

        $this->addCommand('dropAllTables', compact('vacuum'));
        return $this;
    }

    /**
     * Renames the table to the given name.
     *
     * @param string    $name       The new name of the table.
     *
     * @return self
     */
    public function rename(string $name): self
    {
        $this->addCommand('rename', compact('name'));
        return $this;
    }

    /**
     * Renames the given column to the given name.
     *
     * @param string    $from       The name of the column to rename.
     * @param string    $to         The new name of the column.
     *
     * @return self
     */
    public function renameColumn(string $from, string $to): self
    {
        $this->addCommand('renameColumn', compact('from', 'to'));
        return $this;
    }

    /**
     * Drops the column with the given name.
     *
     * @param list<string>|string   $names      The name of the column(s) to drop.
     *
     * @return self
     */
    public function dropColumn(array|string $names): self
    {
        foreach (array_wrap($names) as $name) {
            $this->addCommand('dropColumn', compact('name'));
        }

        return $this;
    }

    /**
     * Builds the SQL statements for the blueprint.
     *
     * @return list<string>
     */
    public function build(): array
    {
        $callback = $this->callback;
        $callback($this);

        return $this->compileCommands();
    }

    /**
     * Adds a column of the given type to the table.
     *
     * @param ColumnType            $type           Column type to create.
     * @param string                $name           Name of the column.
     * @param array<string,mixed>   $parameters     Optional parameters for the column type.
     *
     * @return Column
     */
    public function addColumn(ColumnType $type, string $name, array $parameters = []): Column
    {
        $column = $this->columns[$name] = new Column($this, $name, $type, $parameters);

        return $column;
    }

    /**
     * Creates a new column of `CHAR`.
     *
     * @param string        $name       Name of the column
     * @param null|integer  $length     Max length of the column. Defaults to 255.
     *
     * @return Column
     */
    public function char(string $name, null|int $length = null): Column
    {
        $length ??= 255;

        return $this->addColumn(ColumnType::Char, $name, compact('length'));
    }

    /**
     * Creates a new unbounded column of `TEXT`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function text(string $name): Column
    {
        return $this->addColumn(ColumnType::Text, $name);
    }

    /**
     * Creates a new column of `TINYTEXT`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function tinyText(string $name): Column
    {
        return $this->addColumn(ColumnType::TinyText, $name);
    }

    /**
     * Creates a new column of `MEDIUMTEXT`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function mediumText(string $name): Column
    {
        return $this->addColumn(ColumnType::MediumText, $name);
    }

    /**
     * Creates a new column of `LONGTEXT`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function longText(string $name): Column
    {
        return $this->addColumn(ColumnType::LongText, $name);
    }

    /**
     * Creates a new column of `INTEGER` (4-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function integer(string $name, bool $unsigned = false): Column
    {
        return $this->addColumn(ColumnType::Integer, $name, compact('unsigned'));
    }

    /**
     * Creates a new column of `TINYINT` (1-byte).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function tinyInteger(string $name, bool $unsigned = false): Column
    {
        return $this->addColumn(ColumnType::TinyInteger, $name, compact('unsigned'));
    }

    /**
     * Creates a new column of `SMALLINT` (2-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function smallInteger(string $name, bool $unsigned = false): Column
    {
        return $this->addColumn(ColumnType::SmallInteger, $name, compact('unsigned'));
    }

    /**
     * Creates a new column of `MEDIUMINT` (4-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function mediumInteger(string $name, bool $unsigned = false): Column
    {
        return $this->addColumn(ColumnType::MediumInteger, $name, compact('unsigned'));
    }

    /**
     * Creates a new column of `BIGINT` (8-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function bigInteger(string $name, bool $unsigned = false): Column
    {
        return $this->addColumn(ColumnType::BigInteger, $name, compact('unsigned'));
    }

    /**
     * Creates a new column of `UNSIGNED INT` (4-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function unsignedInteger(string $name): Column
    {
        return $this->integer($name, unsigned: true);
    }

    /**
     * Creates a new column of `UNSIGNED TINYINT` (1-byte).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function unsignedTinyInteger(string $name): Column
    {
        return $this->tinyInteger($name, unsigned: true);
    }

    /**
     * Creates a new column of `UNSIGNED SMALLINT` (2-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function unsignedSmallInteger(string $name): Column
    {
        return $this->smallInteger($name, unsigned: true);
    }

    /**
     * Creates a new column of `UNSIGNED MEDIUMINT` (4-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function unsignedMediumInteger(string $name): Column
    {
        return $this->mediumInteger($name, unsigned: true);
    }

    /**
     * Creates a new column of `UNSIGNED BIGINT` (8-bytes).
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function unsignedBigInteger(string $name): Column
    {
        return $this->bigInteger($name, unsigned: true);
    }

    /**
     * Creates a new column of `DATE`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function date(string $name): Column
    {
        return $this->addColumn(ColumnType::Date, $name);
    }

    /**
     * Creates a new column of `TIMESTAMP`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function timestamp(string $name): Column
    {
        return $this->addColumn(ColumnType::Timestamp, $name);
    }

    /**
     * Creates a new column of `TIMESTAMP WITH TIMEZONE`.
     *
     * @param string        $name       Name of the column
     *
     * @return Column
     */
    public function timestampTz(string $name): Column
    {
        return $this->addColumn(ColumnType::TimestampTz, $name);
    }

    /**
     * Creates a new column of `UNSIGNED BIGINT` (8-bytes) as a primary key.
     *
     * @param string        $name       Name of the column. Defaults to `id`.
     *
     * @return IndexColumn
     */
    public function id(string $name = "id"): IndexColumn
    {
        return $this->unsignedBigInteger($name)->primary();
    }

    /**
     * Creates a index on one-or-more columns.
     *
     * @param string|list<string>   $columns    Columns to include in the index.
     *
     * @return void
     */
    public function index(string|array $columns): void
    {
        $this->indexCommand('index', array_wrap($columns));
    }

    /**
     * Creates a index on one-or-more columns.
     *
     * @param string|list<string>   $columns    Columns to include in the index.
     *
     * @return void
     */
    public function dropIndex(string|array $columns): void
    {
        $this->dropIndexCommand('index', $columns);
    }

    /**
     * Creates a unique constraint on one-or-more columns.
     *
     * @param string|list<string>   $columns    Columns to include in the constraint.
     * @param null|string           $name       Name of the column.
     *
     * @return void
     */
    public function unique(string|array $columns, null|string $name = null): void
    {
        $this->indexCommand('unique', array_wrap($columns), $name);
    }

    /**
     * Drops a unique constraint on one-or-more columns.
     *
     * @param string|list<string>   $columns    Index name or array of columns in the index.
     *
     * @return void
     */
    public function dropUnique(string|array $columns): void
    {
        $this->dropIndexCommand('unique', $columns);
    }

    /**
     * Creates a primary key constraint on one-or-more columns.
     *
     * @param string|list<string>   $columns    Columns to include in the constraint.
     *
     * @return void
     */
    public function primary(string|array $columns): void
    {
        $this->indexCommand('primary', array_wrap($columns));
    }

    /**
     * Creates a new foreign key column which references the model `$model`.
     *
     * @template TModel of Model
     *
     * @param class-string<TModel>      $model      Name of the foreign model.
     * @param null|string               $name       Name of the foreign key column.
     * @param null|string               $table      Name of the referenced table.
     * @param null|string               $key        Name of the referenced column primary key.
     *
     * @return Column
     */
    public function belongsTo(
        string $model,
        null|string $name = null,
        null|string $table = null,
        null|string $key = null
    ): Column {
        $naming = $model::naming();

        return $this->bigInteger(
            $name ?? $naming->foreignKey()
        )->foreign(
            $table ?? $naming->table(),
            $key ?? $model::key()
        );
    }

    /**
     * Adds a new command to the blueprint.
     *
     * @param  string               $name           The name of the command.
     * @param  array<string,mixed>  $parameters     Optional parameters to pass.
     *
     * @return void
     */
    protected function addCommand(string $name, array $parameters = []): void
    {
        $this->commands[] = new Command($name, $parameters);
    }

    /**
     * Creates an index column for the column with the given name.
     *
     * @param string    $name   Name of the column.
     *
     * @return IndexColumn
     */
    public function indexColumn(string $name): IndexColumn
    {
        $column = $this->columns[$name];
        $index = $this->columns[$name] = new IndexColumn($column);

        return $index;
    }

    /**
     * Creates a foreign-key column for the column with the given name.
     *
     * @param string    $name       Name of the column.
     * @param string    $table      Name of the table being referenced.
     * @param string    $key        Name of the column in the table being referenced.
     *
     * @return ForeignKeyColumn
     */
    public function foreignColumn(string $name, string $table, string $key): ForeignKeyColumn
    {
        $column = $this->columns[$name];
        $foreign = $this->columns[$name] = new ForeignKeyColumn($column, $table, $key);

        return $foreign;
    }

    /**
     * Adds a new index command to the blueprint.
     *
     * @param  string           $type           The type of index.
     * @param  list<string>     $columns        Optional parameters to pass.
     * @param  string           $name           The name of the command.
     *
     * @return void
     */
    protected function indexCommand(string $type, array $columns, null|string $name = null): void
    {
        if ($name === null || strlen($name) <= 0) {
            $name = $this->indexName($type, $columns);
        }

        $this->addCommand($type, compact('columns', 'name'));
    }

    /**
     * Drops an index command from the blueprint.
     *
     * @param  string               $type       The type of index.
     * @param  string|list<string>  $name       Index name or array of columns in the index.
     *
     * @return void
     */
    protected function dropIndexCommand(string $type, string|array $name): void
    {
        if (is_array($name)) {
            $name = $this->indexName($type, $name);
        }

        $type = 'drop' . ucfirst($type);

        $this->addCommand($type, compact('name'));
    }

    /**
     * Generates a new index name for the given index type.
     *
     * @param  string           $type           The name of the command.
     * @param  list<string>     $columns        Optional parameters to pass.
     *
     * @return string
     */
    protected function indexName(string $type, array $columns): string
    {
        $name = implode("_", [
            $this->table,
            implode('_', $columns),
            $type,

        ]);

        return str_replace(['-', '.'], '_', strtolower($name));
    }

    /**
     * Compiles the given command into SQL.
     *
     * @param  Command  $command    Command to compile into SQL.
     *
     * @return list<string>|string
     */
    protected function compileCommand(Command $command): array|string
    {
        $methodName = 'compile' . ucfirst($command->name);

        if (method_exists($this->grammar, $methodName)) {
            return $this->grammar->{$methodName}($this, $command);
        }

        return '';
    }

    /**
     * Compiles the commands in the blueprint into SQL.
     *
     * @return list<string>
     */
    protected function compileCommands(): array
    {
        $statements = [];

        foreach ($this->commands as $command) {
            $statements += array_wrap($this->compileCommand($command));
        }

        return $statements;
    }
}
