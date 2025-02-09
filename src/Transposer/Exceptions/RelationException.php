<?php

namespace Brickhouse\Database\Transposer\Exceptions;

abstract class RelationException extends \Exception
{
    public function __construct(
        public readonly string $model,
        public readonly string $relation,
        public readonly string $reason,
    ) {
        $message = "Invalid relation on {$model}::\${$relation}: {$reason}";
        parent::__construct($message);
    }
}
