<?php

namespace Brickhouse\Database\Exceptions;

class InvalidRelationAttributeException extends RelationException
{
    public function __construct(string $model, string $relation)
    {
        parent::__construct($model, $relation, "property has no relation attribute, and no static type has been set.");
    }
}
