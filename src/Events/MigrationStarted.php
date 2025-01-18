<?php

namespace Brickhouse\Database\Events;

use Brickhouse\Database\Migrations\Migration;

final readonly class MigrationStarted
{
    public function __construct(
        public readonly string $name,
        public readonly Migration $migration,
    ) {}
}
