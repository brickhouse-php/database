<?php

namespace Brickhouse\Database\Schema;

class Column
{
    /**
     * Gets whether the column is auto-incrementing.
     */
    public protected(set) bool $autoincrement = false;

    /**
     * Gets whether the column is nullable.
     */
    public protected(set) bool $nullable = false;

    /**
     * Gets whether the column is the primary-key.
     */
    public protected(set) bool $primary = false;

    /**
     * Gets whether the column is unique.
     */
    public protected(set) bool $unique = false;

    /**
     * Gets the default value for the column.
     */
    public protected(set) null|int|string|Expression $default = null;

    /**
     * @param Blueprint             $blueprint
     * @param string                $name
     * @param Blueprint             $blueprint
     * @param ColumnType            $type
     * @param array<string,mixed>   $parameters
     */
    public function __construct(
        public readonly Blueprint $blueprint,
        public readonly string $name,
        public readonly ColumnType $type,
        public readonly array $parameters
    ) {}

    /**
     * Mark the column as auto-incrementing.
     *
     * @param boolean $autoincrement
     *
     * @return self
     */
    public function autoincrement(bool $autoincrement = true): self
    {
        $this->autoincrement = $autoincrement;
        return $this;
    }

    /**
     * Mark the column as nullable.
     *
     * @param boolean $nullable
     *
     * @return self
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * Mark the column as the primary key.
     *
     * @return IndexColumn
     */
    public function primary(): IndexColumn
    {
        $index = $this->blueprint->indexColumn($this->name);
        $index->primary = true;

        return $index;
    }

    /**
     * Mark the column as unique.
     *
     * @return IndexColumn
     */
    public function unique(): IndexColumn
    {
        $index = $this->blueprint->indexColumn($this->name);
        $index->unique = true;

        return $index;
    }

    /**
     * Sets the default value of the column.
     *
     * @return Column
     */
    public function default(null|int|string|Expression $default): Column
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Marks the column as a foreign key to the given table and column.
     *
     * @param string    $table      The name of the table being referenced.
     * @param string    $key        The name of the column in the table being referenced.
     *
     * @return ForeignKeyColumn
     */
    public function foreign(string $table, string $key): ForeignKeyColumn
    {
        return $this->blueprint->foreignColumn($this->name, $table, $key);
    }
}
