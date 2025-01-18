<?php

namespace Brickhouse\Database\Migrations;

use Brickhouse\Database\Schema\Schema;

abstract class Migration
{
    /**
     * Gets the name of the connection to apply the migration to.
     *
     * @var null|string
     */
    public null|string $connection { get => null; }

    /**
     * Run the migrations.
     */
    public abstract function up(Schema $schema): void;

    /**
     * Reverse the migrations.
     */
    public abstract function down(Schema $schema): void;
}
