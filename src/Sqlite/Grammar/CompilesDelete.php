<?php

namespace Brickhouse\Database\Sqlite\Grammar;

use Brickhouse\Database\Builder\QueryBuilder;

trait CompilesDelete
{
    use CompilesConditions;

    /**
     * Compiles an `DELETE` statement with the values given in `$builder`.
     *
     * @param QueryBuilder          $builder
     *
     * @return string
     */
    public function compileDelete(QueryBuilder $builder): string
    {
        $sql = "DELETE FROM {$builder->table}";

        if (!empty($builder->conditions)) {
            $sql .= " " . $this->compileWheres($builder);
        }

        return $sql;
    }
}
