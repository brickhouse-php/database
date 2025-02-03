<?php

namespace Brickhouse\Database\Transposer\Exceptions;

class InvalidRelationException extends RelationException
{
    public function __construct(string $model, string $relation)
    {
        parent::__construct($model, $relation, "property must be public, non-static, non-abstract and non-readonly.");
    }
}
