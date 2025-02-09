<?php

namespace Brickhouse\Database\Transposer\Exceptions;

class UnloadedRelationException extends RelationException
{
    public function __construct(string $model, string $relation)
    {
        parent::__construct($model, $relation, "relation is not loaded on model.");
    }
}
