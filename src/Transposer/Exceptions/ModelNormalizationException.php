<?php

namespace Brickhouse\Database\Transposer\Exceptions;

class ModelNormalizationException extends \InvalidArgumentException
{
    public function __construct(string $model, string $message)
    {
        $message = "Normalization failed on {$model}: {$message}";

        parent::__construct($message);
    }
}
