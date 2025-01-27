<?php

namespace Brickhouse\Database\Sqlite\Grammar;

use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Column;
use Brickhouse\Database\Schema\ColumnType;
use Brickhouse\Database\Schema\Command;
use Brickhouse\Database\Schema\Expression;
use Brickhouse\Database\Schema\ForeignKeyColumn;
use Brickhouse\Database\Schema\ForeignKeyTrigger;
use Brickhouse\Database\Schema\IndexColumn;

trait CompilesSchema
{
    /**
     * The constraints which are allowed on columns.
     *
     * @var string[]
     */
    protected $columnContraints = [
        'primary',
        'autoIncrement',
        'nullable',
        'unique',
        'conflict',
        'default',
        'foreign',
    ];

    /**
     * Compiles an SQL statement to create a new table.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Command $command): string
    {
        $columns = $this->createBlueprintColumns($blueprint);

        return "CREATE TABLE {$blueprint->table} ({$columns})";
    }

    /**
     * Compiles an SQL statement to create a new table, if it doesn't already exist.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileCreateIfNotExists(Blueprint $blueprint, Command $command): string
    {
        $columns = $this->createBlueprintColumns($blueprint);

        return "CREATE TABLE IF NOT EXISTS {$blueprint->table} ({$columns})";
    }

    /**
     * Compiles an SQL statement to alter a table.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return list<string>
     */
    public function compileAlter(Blueprint $blueprint, Command $command): array
    {
        $columns = array_map([$this, "createTableColumn"], $blueprint->columns);

        $commands = array_map(
            fn(string $column) => "ALTER TABLE {$blueprint->table} ADD COLUMN {$column}",
            $columns
        );

        return array_values($commands);
    }

    /**
     * Compiles an SQL statement to drop a table.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Command $command): string
    {
        return "DROP TABLE {$blueprint->table}";
    }

    /**
     * Compiles an SQL statement to drop a table, if it exists.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Command $command): string
    {
        return "DROP TABLE IF EXISTS {$blueprint->table}";
    }

    /**
     * Compiles an SQL statement to drop all table in the database.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return list<string>
     */
    public function compileDropAllTables(Blueprint $blueprint, Command $command): array
    {
        ['vacuum' => $vacuum] = $command->parameters;

        $commands = [
            "PRAGMA writable_schema = 1",
            "DELETE FROM sqlite_master WHERE type IN ('table', 'index', 'trigger')",
            "PRAGMA writable_schema = 0",
        ];

        if ($vacuum) {
            $commands[] = "VACUUM";
        }

        return $commands;
    }

    /**
     * Compiles an SQL statement to rename a table.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Command $command): string
    {
        ['name' => $newName] = $command->parameters;

        return "ALTER TABLE {$blueprint->table} RENAME TO {$newName}";
    }

    /**
     * Compiles an SQL statement to rename a column.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileRenameColumn(Blueprint $blueprint, Command $command): string
    {
        ['from' => $from, 'to' => $to] = $command->parameters;

        return "ALTER TABLE {$blueprint->table} RENAME COLUMN {$from} TO {$to}";
    }

    /**
     * Compiles an SQL statement to drop a column.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Command $command): string
    {
        ['name' => $name] = $command->parameters;

        return "ALTER TABLE {$blueprint->table} DROP COLUMN {$name}";
    }

    /**
     * Compiles an SQL statement to create a new index.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Command $command): string
    {
        ['columns' => $columns, 'name' => $name] = $command->parameters;

        $columns = join(", ", $columns);

        return "CREATE INDEX {$name} ON {$blueprint->table}({$columns})";
    }

    /**
     * Compiles an SQL statement to drop an index.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Command $command): string
    {
        ['name' => $name] = $command->parameters;

        return "DROP INDEX {$name}";
    }

    /**
     * Compiles an SQL statement to create a new unique constraint.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Command $command): string
    {
        ['columns' => $columns, 'name' => $name] = $command->parameters;

        $columns = join(", ", $columns);

        return "CREATE UNIQUE INDEX {$name} ON {$blueprint->table}({$columns})";
    }

    /**
     * Compiles an SQL statement to drop a unique constraint.
     *
     * @param Blueprint     $blueprint
     * @param Command       $command
     *
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Command $command): string
    {
        ['name' => $name] = $command->parameters;

        return "DROP INDEX {$name}";
    }

    /**
     * Compiles a partial SQL statement to create columns on a table.
     *
     * @param Blueprint $blueprint
     *
     * @return string
     */
    public function createBlueprintColumns(Blueprint $blueprint): string
    {
        return join(", ", array_map([$this, "createTableColumn"], $blueprint->columns));
    }

    /**
     * Compiles a partial SQL statement to create a column on a table.
     *
     * @param Column    $column     The column the create the SQL statement from.
     *
     * @return string
     */
    public function createTableColumn(Column $column): string
    {
        $typeName = $this->mapColumnType($column->type);
        $constraints = $this->compileTableColumnConstraints($column);

        return trim("{$column->name} {$typeName} {$constraints}");
    }

    protected function mapColumnType(ColumnType $type): string
    {
        return match ($type) {
            ColumnType::Char => "VARCHAR",
            ColumnType::String => "VARCHAR",
            ColumnType::TinyText => "TEXT",
            ColumnType::Text => "TEXT",
            ColumnType::MediumText => "TEXT",
            ColumnType::LongText => "TEXT",
            ColumnType::Integer => "INTEGER",
            ColumnType::TinyInteger => "INTEGER",
            ColumnType::SmallInteger => "INTEGER",
            ColumnType::MediumInteger => "INTEGER",
            ColumnType::BigInteger => "INTEGER",
            ColumnType::Float => "FLOAT",
            ColumnType::Double => "DOUBLE",
            ColumnType::Date => "TEXT",
            ColumnType::Timestamp => "TEXT",
            ColumnType::TimestampTz => "TEXT",
        };
    }

    protected function compileTableColumnConstraints(Column $column): string
    {
        $sql = [];

        foreach ($this->columnContraints as $component) {
            $method = 'compileTableColumn' . ucfirst($component);

            $sql[$component] = trim($this->{$method}($column));
        }

        return implode(
            " ",
            array_filter(
                $sql,
                fn(string $value) => $value !== ''
            )
        );
    }

    protected function compileTableColumnAutoIncrement(Column $column): string
    {
        if ($column->primary) {
            return "";
        }

        return $column->autoincrement ? "AUTOINCREMENT" : "";
    }

    protected function compileTableColumnNullable(Column $column): string
    {
        if ($column->primary) {
            return "";
        }

        return $column->nullable ? "" : "NOT NULL";
    }

    protected function compileTableColumnPrimary(Column $column): string
    {
        return $column->primary ? "PRIMARY KEY" : "";
    }

    protected function compileTableColumnUnique(Column $column): string
    {
        return $column->unique ? "UNIQUE" : "";
    }

    protected function compileTableColumnConflict(Column $column): string
    {
        if ($column instanceof IndexColumn && $column->onConflict !== null) {
            return "ON CONFLICT {$column->onConflict}";
        }

        return "";
    }

    protected function compileTableColumnDefault(Column $column): string
    {
        if (!$column->default) {
            return "";
        }

        $default = $column->default;

        if (is_string($default)) {
            $default = "\"{$default}\"";
        }

        if ($default instanceof Expression) {
            $default = $default->expression;
        }

        return "DEFAULT {$default}";
    }

    protected function compileTableColumnForeign(Column $column): string
    {
        if (!$column instanceof ForeignKeyColumn) {
            return "";
        }

        $references = $column->table;
        $key = $column->key;

        // When creating a table, we have to add the `FOREIGN KEY` to another line.
        // This isn't the case when altering the table.
        // Creating: `CREATE TABLE ... (user_id INT, FOREIGN KEY(user_id) REFERENCES users(id));`
        // Altering: `ALTER TABLE ... ADD COLUMN user_id INT REFERENCES users(id);`
        if ($column->blueprint->change === 'create') {
            $sql = ", FOREIGN KEY({$column->name}) REFERENCES {$references}({$key})";
        } else {
            $sql = "REFERENCES {$references}({$key})";
        }

        if ($column->onUpdate !== null) {
            $sql .= " ON UPDATE " . $this->mapForeignColumnTrigger($column->onUpdate);
        }

        if ($column->onDelete !== null) {
            $sql .= " ON DELETE " . $this->mapForeignColumnTrigger($column->onDelete);
        }

        return $sql;
    }

    protected function mapForeignColumnTrigger(ForeignKeyTrigger $trigger): string
    {
        return match ($trigger) {
            ForeignKeyTrigger::Ignore => "NO ACTION",
            ForeignKeyTrigger::Restrict => "RESTRICT",
            ForeignKeyTrigger::SetNull => "SET NULL",
            ForeignKeyTrigger::SetDefault => "SET DEFAULT",
            ForeignKeyTrigger::Cascade => "CASCADE",
        };
    }
}
