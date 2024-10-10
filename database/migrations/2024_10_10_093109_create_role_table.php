<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('space_id')->references('id')->on('space');
            $table->string('name', 64)->unique();
        });

        DB::unprepared('
            CREATE UNIQUE INDEX idx_system_role ON role (name) WHERE space_id = NULL;
            CREATE UNIQUE INDEX idx_not_system_role ON role (name, space_id) WHERE space_id != NULL;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP INDEX idx_system_role;
            DROP idx_not_system_role;
        ');
        Schema::dropIfExists('role');
    }
};
