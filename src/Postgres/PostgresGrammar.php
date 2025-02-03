<?php

namespace Brickhouse\Database\Postgres;

use Brickhouse\Database\Postgres\Grammar;

class PostgresGrammar extends \Brickhouse\Database\Grammar
{
    use Grammar\CompilesSelect;
    use Grammar\CompilesInsert;
    use Grammar\CompilesUpdate;
    use Grammar\CompilesDelete;

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
        '!=',
        'is',
        'is not',
        'like',
        'not like',
        'ilike',
        'in',
        'not in',
        '#',
        '&',
        '|',
        '<<',
        '>>',
    ];
}
