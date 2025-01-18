<?php

namespace Brickhouse\Database\Sqlite;

use Brickhouse\Database\Sqlite\Grammar;

class SqliteGrammar extends \Brickhouse\Database\Grammar
{
    use Grammar\CompilesSelect;
    use Grammar\CompilesInsert;
    use Grammar\CompilesUpdate;
    use Grammar\CompilesDelete;
    use Grammar\CompilesSchema;

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
        'like',
        'not like',
        'ilike',
        'in',
        'not in',
        '&',
        '|',
        '<<',
        '>>',
    ];
}
