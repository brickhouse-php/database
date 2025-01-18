<?php

namespace Brickhouse\Database\Sqlite\Grammar;

use Brickhouse\Database\Builder\QueryBuilder;

trait CompilesUpdate
{
    /**
     * Compiles an `UPDATE` statement with the values given in `$builder`.
     *
     * @param QueryBuilder          $builder
     * @param array<string,mixed>   $values
     *
     * @return string
     */
    public function compileUpdate(QueryBuilder $builder, array $values): string
    {
        $columns = join(
            ", ",
            array_map(
                fn(string $key) => $key . " = ?",
                array_keys($values)
            )
        );

        $builder->addBoundValues($values);

        $sql = "UPDATE {$builder->table} SET {$columns}";
        if (!empty($builder->conditions)) {
            $sql .= " " . $this->compileWheres($builder);
        }

        return $sql;
    }
}
