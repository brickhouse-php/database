<?php

namespace Brickhouse\Database;

class Join
{
    /**
     * @param string        $table          Table name to join with.
     * @param string        $left           Defines the left side of the join.
     * @param string        $operator       Defines the operator to use in the join comparison.
     * @param string        $right          Defines the right side of the join.
     * @param string        $type           Defines the type of join clause (e.g. `INNER`, `LEFT`, etc.).
     */
    public function __construct(
        public readonly string $type,
        public readonly string $table,
        public readonly string $left,
        public readonly string $operator,
        public readonly string $right,
    ) {}
}
