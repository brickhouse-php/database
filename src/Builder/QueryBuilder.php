<?php

namespace Brickhouse\Database\Builder;

use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\Grammar;
use Brickhouse\Database\Join;
use Brickhouse\Support\Collection;

/**
 * @phpstan-type Bindings   array<int|string,string>
 * @phpstan-type Condition  array{column:string,operator:string,value:mixed}
 */
class QueryBuilder
{
    /**
     * Defines which table is being operated on.
     *
     * @var string
     */
    public private(set) string $table;

    /**
     * Defines which columns are to be returned.
     *
     * @var array<int|string,string>
     */
    public private(set) array $columns = ['*'];

    /**
     * Defines which bindings are bound to the query.
     *
     * @var Bindings
     */
    public private(set) array $bindings = [];

    /**
     * Defines which conditions are required to be met.
     *
     * @var array<int,Condition>
     */
    public private(set) array $conditions = [];

    /**
     * Defines which conditions are required to be met.
     *
     * @var array<int,Join>
     */
    public private(set) array $joins = [];

    /**
     * Gets the grammar used for the query.
     *
     * @var Grammar
     */
    public Grammar $grammar {
        get => $this->connection->grammar;
    }

    /**
     * Defines whether the query should only affect distinct rows.
     *
     * @var bool
     */
    public private(set) bool $distinct = false;

    /**
     * Defines the row offset of the query.
     *
     * @var null|int
     */
    public private(set) null|int $offset = null;

    /**
     * Defines the row limit of the query.
     *
     * @var null|int
     */
    public private(set) null|int $limit = null;

    /**
     * Creates a new query builder on the given database connection.
     *
     * @param DatabaseConnection $connection
     */
    public function __construct(
        protected readonly DatabaseConnection $connection,
    ) {}

    /**
     * Creates a new instance of the query builder with the same connection parameters.
     *
     * @return QueryBuilder
     */
    public function clone(): QueryBuilder
    {
        return new QueryBuilder($this->connection);
    }

    /**
     * Selects which table the query should operate on.
     *
     * @param string    $table
     *
     * @return self
     */
    public function from(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Defines whether the query should select 'distinct' values or not.
     *
     * @param bool  $distinct   Whether the query should be distinct. Defaults to `true`.
     *
     * @return self
     */
    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Adds a conditional `WHERE` clause to the query, limiting what rows/records are affected by the query.
     *
     * @param string    $column             Name of the column to apply the condition on.
     * @param mixed     $operatorOrValue    Either the operator for the clause, or the value the column should equal to.
     * @param mixed     $value              If `$operatorOrValue` is an operator, defines the value the column should equal to.
     *
     * @return self
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operatorOrValue;
            $operatorOrValue = '=';
        }

        $operatorOrValue = strtolower($operatorOrValue);

        if (!in_array($operatorOrValue, $this->grammar->operators)) {
            throw new \RuntimeException("Invalid WHERE operator: {$operatorOrValue}");
        }

        $this->conditions[] = [
            'column' => $column,
            'operator' => $operatorOrValue,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Adds a conditional `WHERE` clause to the query, limiting what rows/records are affected by the query.
     *
     * @param string            $column             Name of the column to apply the condition on.
     * @param array<int,mixed>  $values             Defines the value the column should equal to.
     *
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        return $this->where($column, 'IN', $values);
    }

    /**
     * Skips the first `$amount` of rows.
     *
     * @param int   $amount     The amount of rows to skip over.
     *
     * @return self
     */
    public function skip(int $amount): self
    {
        $this->offset = $amount;

        return $this;
    }

    /**
     * Limits the amount of rows to return to, at most, `$amount` rows.
     *
     * @param int   $amount     The maximum amount of rows to return.
     *
     * @return self
     */
    public function take(int $amount): self
    {
        $this->limit = $amount;

        return $this;
    }

    /**
     * Adds a join clause to the query.
     *
     * @param string    $table          Table name to join with.
     * @param string    $left           Defines the left side of the join.
     * @param string    $operator       Defines the operator to use in the join comparison.
     * @param string    $right          Defines the right side of the join.
     * @param string    $type           Defines the type of join clause (e.g. `INNER`, `LEFT`, etc.).
     *
     * @return self
     */
    public function joinClause(string $table, string $left, string $operator, string $right, string $type): self
    {
        $this->joins[] = new Join($type, $table, $left, $operator, $right);

        return $this;
    }

    /**
     * Adds an inner join to the query.
     *
     * @param string    $table          Table name to join with.
     * @param string    $left           Defines the left side of the join.
     * @param string    $operator       Defines the operator to use in the join comparison.
     * @param string    $right          Defines the right side of the join.
     *
     * @return self
     */
    public function join(string $table, string $left, string $operator, string $right): self
    {
        return $this->joinClause($table, $left, $operator, $right, type: 'INNER');
    }

    /**
     * Adds a left join to the query.
     *
     * @param string    $table          Table name to join with.
     * @param string    $left           Defines the left side of the join.
     * @param string    $operator       Defines the operator to use in the join comparison.
     * @param string    $right          Defines the right side of the join.
     *
     * @return self
     */
    public function leftJoin(string $table, string $left, string $operator, string $right): self
    {
        return $this->joinClause($table, $left, $operator, $right, type: 'LEFT');
    }

    /**
     * Adds a right join to the query.
     *
     * @param string    $table          Table name to join with.
     * @param string    $left           Defines the left side of the join.
     * @param string    $operator       Defines the operator to use in the join comparison.
     * @param string    $right          Defines the right side of the join.
     *
     * @return self
     */
    public function rightJoin(string $table, string $left, string $operator, string $right): self
    {
        return $this->joinClause($table, $left, $operator, $right, type: 'RIGHT');
    }

    /**
     * Adds a cross join to the query.
     *
     * @param string    $table          Table name to join with.
     * @param string    $left           Defines the left side of the join.
     * @param string    $operator       Defines the operator to use in the join comparison.
     * @param string    $right          Defines the right side of the join.
     *
     * @return self
     */
    public function crossJoin(string $table, string $left, string $operator, string $right): self
    {
        return $this->joinClause($table, $left, $operator, $right, type: 'CROSS');
    }

    /**
     * Defines which columns should be selected from the query.
     *
     * @param string|array<int|string,string>   $columns    String or array of strings. If array, can be keyed to defined column aliases.
     *
     * @return self
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->columns = [];
        $this->addSelectedColumn($columns);

        return $this;
    }

    /**
     * @param string    $selection
     * @param Bindings  $bindings
     *
     * @return self
     */
    public function selectRaw(string $selection, array $bindings = []): self
    {
        $this->columns = [];
        $this->addSelectedColumn($selection);
        $this->addBoundValues($bindings);

        return $this;
    }

    /**
     * Inserts the given values into the table.
     *
     * @param array<string,mixed>|list<array<string,mixed>>     $values
     *
     * @return list<array<string,mixed>>
     */
    public function insert(array $values): array
    {
        $query = $this->grammar->compileInsert($this, $values, returning: true);
        $result = $this->connection->select($query, $this->bindings);

        return $result;
    }

    /**
     * Updates the given values in the table.
     *
     * @param array<string,mixed>|list<array<string,mixed>>     $values
     *
     * @return bool
     */
    public function update(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $query = $this->grammar->compileUpdate($this, $values);
        $result = $this->connection->statement($query, $this->bindings);

        return $result;
    }

    /**
     * Deletes the selected values from the table.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $query = $this->grammar->compileDelete($this);
        $result = $this->connection->statement($query, $this->bindings);

        return $result;
    }

    /**
     * @param null|string|list<string>  $columns    Defines which columns to retrieve. If not `null`, overwrites previous selection.
     *
     * @return Collection<int,mixed>
     */
    public function get(null|string|array $columns = null): Collection
    {
        if ($columns) {
            $this->columns = array_wrap($columns);
        }

        $query = $this->grammar->compileSelect($this);
        $results = $this->connection->select($query, $this->bindings);

        return Collection::wrap($results);
    }

    /**
     * Executes the query and extract only the given column from the result.
     *
     * @param string    $column     Defines which column to select.
     *
     * @return Collection<int,mixed>
     */
    public function value(string $column): Collection
    {
        $this->columns = [$column];

        $query = $this->grammar->compileSelect($this);
        $results = $this->connection->select($query, $this->bindings);

        return Collection::wrap($results)->flatten();
    }

    /**
     * Executes the query and get the first result. If no results are returned, returns `null`.
     *
     * @param null|string|list<string>  $columns    Defines which columns to select. If not `null`, overwrites previously selected columns.
     *
     * @return null|array<string,mixed>
     */
    public function first(null|string|array $columns = null): null|array
    {
        if ($columns) {
            $this->columns = array_wrap($columns);
        }

        $query = $this->grammar->compileSelect($this);
        $row = $this->connection->selectSingle($query, $this->bindings);

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * Executes the query and get the first result. If no results are returned, throws an exception.
     *
     * @param null|string|list<string>  $columns    Defines which columns to select. If not `null`, overwrites previously selected columns.
     *
     * @return array<string,mixed>
     *
     * @throws \RuntimeException    Thrown if no results were returned from the query.
     */
    public function firstOrFail(null|string|array $columns = null): array
    {
        if (($row = $this->first($columns)) !== null) {
            return $row;
        }

        throw new \RuntimeException("No more rows found in table: {$this->table}");
    }

    /**
     * Executes the query and get the first result. If no results are returned, returns the result of `$fallback`.
     *
     * @template TValue
     *
     * @param string|array<int|string,string>|(\Closure():TValue)   $columns
     * @param null|(\Closure():TValue)                              $fallback
     *
     * @return array<string,mixed>|TValue
     */
    public function firstOr(null|string|array|\Closure $columns = null, null|\Closure $fallback = null): mixed
    {
        if ($columns instanceof \Closure) {
            $fallback = $columns;
            $columns = ['*'];
        }

        if (($row = $this->first($columns)) !== null) {
            return $row;
        }

        return $fallback();
    }

    /**
     * @return Collection<int,mixed>
     */
    public function pluck(string $keyOrValue, null|string $value = null): Collection
    {
        if (is_null($value)) {
            $columns = [$keyOrValue];
        } else {
            $columns = [$keyOrValue, $value];
        }

        $result = $this->get($columns);

        return $result->pluck(...func_get_args());
    }

    /**
     * Adds a column to be selected by the query.
     *
     * @param array<int|string,string>|string   $column
     *
     * @return void
     */
    public function addSelectedColumn(array|string $column): void
    {
        /** @var array<int|string,string> $columns */
        $columns = array_wrap($column);

        foreach ($columns as $as => $column) {
            if (is_string($as)) {
                $this->columns[$as] = $column;
            } else {
                if (in_array($column, $this->columns, strict: true)) {
                    continue;
                }

                $this->columns[] = $column;
            }
        }
    }

    /**
     * Adds a bound value to the query.
     *
     * @param array<int|string,string|bool|int>|string|bool|int     $values
     *
     * @return void
     */
    public function addBoundValues(array|string|bool|int ...$values): void
    {
        foreach ($values as $value) {
            $this->addBoundValue($value);
        }
    }

    /**
     * Adds a bound value to the query.
     *
     * @param array<int|string,string|bool|int>|string|bool|int     $value
     *
     * @return void
     */
    public function addBoundValue(array|string|bool|int $value): void
    {
        if (!is_array($value)) {
            $this->bindings[] = $value;
            return;
        }

        foreach ($value as $key => $binding) {
            if (is_string($key)) {
                $this->bindings[$key] = $binding;
            } else {
                $this->bindings[] = $binding;
            }
        }
    }

    /**
     * Replaces a condition in the query.
     *
     * @param int           $idx
     * @param Condition     $replacement
     *
     * @return void
     */
    public function replaceCondition(int $idx, array $replacement): void
    {
        $this->conditions[$idx] = $replacement;
    }
}
