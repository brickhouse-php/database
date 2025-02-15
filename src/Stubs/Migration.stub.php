<?php

use Brickhouse\Database\Migrations\Migration;
use Brickhouse\Database\Schema\Blueprint;
use Brickhouse\Database\Schema\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(Schema $schema): void
    {
        $schema->create('{{ tableName }}', function (Blueprint $table) {
            $table->id();

            // Add further columns here...
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(Schema $schema): void
    {
        $schema->drop('{{ tableName }}');
    }
};
