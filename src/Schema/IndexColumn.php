<?php

namespace Brickhouse\Database\Schema;

use Brickhouse\Database\Schema\Concerns;
use Brickhouse\Reflection\ReflectedType;

class IndexColumn extends Column
{
    use Concerns\HasConflictClause;

    public function __construct(
        protected readonly Column $column
    ) {
        parent::__construct(
            $column->blueprint,
            $column->name,
            $column->type,
            $column->parameters
        );

        $reflector = new ReflectedType($column::class);
        foreach ($reflector->getProperties() as $property) {
            if ($property->public() && $property->hooked()) {
                $property->setValue($this, $property->value($column));
            }
        }
    }
}
