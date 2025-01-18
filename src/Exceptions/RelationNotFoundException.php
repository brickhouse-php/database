<?php

namespace Brickhouse\Database\Exceptions;

class RelationNotFoundException extends RelationException
{
    public function __construct(string $model, string $relation)
    {
        parent::__construct($model, $relation, "property does not exist on model.");
    }
}
