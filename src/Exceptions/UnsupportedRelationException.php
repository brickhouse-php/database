<?php

namespace Brickhouse\Database\Exceptions;

class UnsupportedRelationException extends RelationException
{
    public function __construct(string $model, string $relation, string $relationType)
    {
        parent::__construct(
            $model,
            $relation,
            "unsupported relation type: {$relationType}."
        );
    }
}
