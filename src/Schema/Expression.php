<?php

namespace Brickhouse\Database\Schema;

final readonly class Expression
{
    public function __construct(
        public readonly string $expression,
    ) {}
}
