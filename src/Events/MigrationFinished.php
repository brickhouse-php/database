<?php

namespace Brickhouse\Database\Events;

use Brickhouse\Database\Migrations\Migration;

final readonly class MigrationFinished
{
    public function __construct(
        public readonly string $name,
        public readonly Migration $migration,
    ) {}
}
