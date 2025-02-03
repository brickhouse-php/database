<?php

namespace Brickhouse\Database\Tests;

use Brickhouse\Core\Application;

abstract class TestCase extends \Brickhouse\Testing\TestCase
{
    public static function setUpBeforeClass(): void
    {
        Application::create();
    }
}
