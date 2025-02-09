<?php

namespace Brickhouse\Database\Sqlite\Grammar;

use Brickhouse\Database\Builder\QueryBuilder;

/**
 * @phpstan-import-type Condition from QueryBuilder
 */
trait CompilesConditions
{
    /**
     * Compile the condition clauses into a partial SQL statement.
     *
     * This method compiles the `WHERE deleted = false` parts of the query.
     *
     * @param QueryBuilder $builder
     *
     * @return string
     */
    protected function compileWheres(QueryBuilder $builder): string
    {
        if (empty($builder->conditions)) {
            return "";
        }

        $clauses = [];

        foreach ($builder->conditions as $condition) {
            ['column' => $column, 'operator' => $operator, 'value' => $value] = $condition;

            $clauses[] = match ($operator) {
                'in' => $this->compileWhereIns($builder, $condition),
                default => "{$column} {$operator} ?",
            };

            $builder->addBoundValues($value);
        }

        return "WHERE " . join(" AND ", $clauses);
    }

    /**
     * Compile the condition clauses into a partial SQL statement.
     *
     * This method compiles the `WHERE id IN [1, 2, 3]` parts of the query.
     *
     * @param QueryBuilder  $builder
     * @param Condition     $condition
     *
     * @return string
     */
    protected function compileWhereIns(QueryBuilder $builder, array $condition): string
    {
        ['column' => $column, 'value' => $value] = $condition;

        $boundValueCount = is_array($value) ? count($value) : 1;
        $boundValues = implode(", ", array_fill(0, $boundValueCount, "?"));

        return "{$column} IN ({$boundValues})";
    }
}
