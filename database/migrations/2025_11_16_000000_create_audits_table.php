<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This migration was superseded by the package migration
        // Keep as a no-op to avoid duplicate table creation.
        return;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // no-op
    }
};
