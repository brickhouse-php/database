<?php

namespace Brickhouse\Database\Postgres\Grammar;

use Brickhouse\Database\Builder\QueryBuilder;

trait CompilesInsert
{
    /**
     * Compiles an `INSERT` statement with the values given in `$builder`.
     *
     * @param QueryBuilder                                      $builder
     * @param array<string,mixed>|list<array<string,mixed>>     $values
     * @param bool                                              $returning
     *
     * @return string
     */
    public function compileInsert(QueryBuilder $builder, array $values, bool $returning = false): string
    {
        if (empty($values)) {
            return "INSERT INTO {$builder->table} DEFAULT VALUES;";
        }

        if (!array_is_list($values)) {
            $values = [$values];
        }

        $columns = $this->resolveColumnsFromValues($values);
        $parameters = $this->compileInsertedParameters($values);

        foreach ($values as $value) {
            foreach ($columns as $column) {
                $builder->addBoundValue($value[$column]);
            }
        }

        $columns = join(", ", $columns);

        $sql = "INSERT INTO {$builder->table} ({$columns}) VALUES {$parameters}";
        if ($returning) {
            $sql .= " RETURNING *";
        }

        return $sql;
    }

    /**
     * Resolves all the unique columns from the array of values given.
     *
     * @param list<array<string,mixed>>     $values
     *
     * @return list<string>
     */
    private function resolveColumnsFromValues(array $values): array
    {
        /** @var list<array<string,mixed>> $columns */
        $columns = array_map(array_keys(...), $values);
        $columns = array_merge(...$columns);
        $columns = array_unique($columns);

        /** @phpstan-ignore return.type */
        return $columns;
    }

    /**
     * Resolves all the unique columns from the array of values given.
     *
     * @param list<array<string,mixed>>     $values
     *
     * @return string
     */
    private function compileInsertedParameters(array $values): string
    {
        $values = array_map(function (array $record) {
            return '(' . join(", ", array_fill(0, count($record), '?')) . ')';
        }, $values);

        return implode(", ", $values);
    }
}
