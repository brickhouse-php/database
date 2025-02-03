<?php

namespace Brickhouse\Database\Postgres\Grammar;

use Brickhouse\Database\Builder\QueryBuilder;

trait CompilesSelect
{
    use CompilesConditions;

    /**
     * The components that make up a `SELECT` clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'columns',
        'from',
        'joins',
        'wheres',
        'sliding',
    ];

    /**
     * Compiles a `SELECT` query with the values given in `$builder`.
     *
     * If any bindings are required, they are added via the `addBoundValue` on the builder.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    public function compileSelect(QueryBuilder $builder): string
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            $method = 'compile' . ucfirst($component);

            $sql[$component] = $this->{$method}($builder);
        }

        return implode(
            " ",
            array_filter(
                $sql,
                fn(string $value) => trim($value) !== ''
            )
        );
    }

    /**
     * Compile the selected columns into a partial SQL statement.
     *
     * This method compiles the `id, username, firstName AS first_name` parts of the query.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    protected function compileColumns(QueryBuilder $builder): string
    {
        $formattedColumns = array_map(
            function (string $column, int|string $as) {
                if (is_int($as)) {
                    return $column;
                }

                return "{$column} AS {$as}";
            },
            array_values($builder->columns),
            array_keys($builder->columns),
        );

        $formattedColumns = join(", ", $formattedColumns);

        if ($builder->distinct) {
            return "SELECT DISTINCT {$formattedColumns}";
        }

        return "SELECT {$formattedColumns}";
    }

    /**
     * Compile the select table into a partial SQL statement.
     *
     * This method compiles the `FROM users` parts of the query.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    protected function compileFrom(QueryBuilder $builder): string
    {
        return "FROM " . $builder->table;
    }

    /**
     * Compile the joins into a partial SQL statement.
     *
     * This method compiles the `INNER JOIN invoices ON invoices.paid_by = users.id` parts of the query.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    protected function compileJoins(QueryBuilder $builder): string
    {
        $sql = [];

        foreach ($builder->joins as $join) {
            $type = strtoupper($join->type);

            $sql[] = "{$type} JOIN {$join->table} ON {$join->left} {$join->operator} {$join->right}";
        }

        return join(" ", $sql);
    }

    /**
     * Compile the offset and limit clauses into a partial SQL statement.
     *
     * This method compiles the `LIMIT 5 OFFSET 1` parts of the query.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    protected function compileSliding(QueryBuilder $builder): string
    {
        $sql = "";

        if ($builder->limit) {
            $sql .= " LIMIT {$builder->limit}";
        }

        if ($builder->offset) {
            $sql .= " OFFSET {$builder->offset}";
        }

        return trim($sql);
    }
}
