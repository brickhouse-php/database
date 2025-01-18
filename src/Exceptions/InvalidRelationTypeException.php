<?php

namespace Brickhouse\Database\Exceptions;

use Brickhouse\Database\Model;

class InvalidRelationTypeException extends RelationException
{
    public function __construct(string $model, string $relation)
    {
        parent::__construct(
            $model,
            $relation,
            "when no relation attribute is set, relation model must implement `" . Model::class . "` interface."
        );
    }
}
