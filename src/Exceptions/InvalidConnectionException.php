<?php

namespace Brickhouse\Database\Exceptions;

class InvalidConnectionException extends \Exception
{
    public function __construct(null|string $name)
    {
        $message = "Invalid connection name given: ";

        if ($name === null) {
            $message .= "attempted to use default connection, but none were given.";
        } else {
            $message .= "attempted to use '{$name}', but it was not found.";
        }

        parent::__construct($message);
    }
}
