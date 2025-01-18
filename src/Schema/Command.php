<?php

namespace Brickhouse\Database\Schema;

final class Command
{
    /**
     * @param string                $name           Gets the name of the command to execute.
     * @param array<string,mixed>   $parameters     Gets optional parameters supplied to the command.
     */
    public function __construct(public readonly string $name, public readonly array $parameters = [])
    {
    }
}
