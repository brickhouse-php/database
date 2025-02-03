<?php

use Brickhouse\Database\Tests;

pest()
    ->extend(Tests\TestCase::class)
    ->in('Feature/Logging', 'Feature/Model', 'Feature/Sqlite');

pest()
    ->extend(Tests\PostgresTestCase::class)
    ->group('integration')
    ->in('Feature/Postgres');
