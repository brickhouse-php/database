<?php

namespace Brickhouse\Database\Sqlite\Grammar;

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Support\Collection;

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

        /** @var array<int,string> $columns */
        $columns = array_flatten(array_map(fn(array $values) => array_keys($values), $values));
        $columns = array_unique($columns);

        $parameters = new Collection($values)->map(function (array $record) {
            return '(' . join(", ", array_fill(0, count($record), '?')) . ')';
        })->join(", ");

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
}
