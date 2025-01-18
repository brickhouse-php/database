<?php

namespace Brickhouse\Database;

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\Schema\Column;

/**
 * @phpstan-import-type Condition from QueryBuilder
 */
abstract class Grammar
{
    /**
     * The valid operators for clauses.
     *
     * @var string[]
     */
    public array $operators = [
        '=',
        '!=',
        '>=',
        '<=',
        '>',
        '<',
        '<>',
        'in',
        'not in',
        'like',
        'not like',
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
    public abstract function compileSelect(QueryBuilder $builder): string;

    /**
     * Compiles an `INSERT` statement with the values given in `$builder`.
     *
     * @param QueryBuilder                                      $builder
     * @param array<string,mixed>|list<array<string,mixed>>     $values
     * @param bool                                              $returning
     *
     * @return string
     */
    public abstract function compileInsert(QueryBuilder $builder, array $values, bool $returning = false): string;

    /**
     * Compiles an `UPDATE` statement with the values given in `$builder`.
     *
     * @param QueryBuilder          $builder
     * @param array<string,mixed>   $values
     *
     * @return string
     */
    public abstract function compileUpdate(QueryBuilder $builder, array $values): string;

    /**
     * Compiles an `DELETE` statement with the values given in `$builder`.
     *
     * @param QueryBuilder          $builder
     *
     * @return string
     */
    public abstract function compileDelete(QueryBuilder $builder): string;
}
