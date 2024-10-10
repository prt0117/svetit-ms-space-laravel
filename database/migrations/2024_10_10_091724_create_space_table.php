<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Expression;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('space', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(new Expression('(GEN_RANDOM_UUID())'));
            $table->string('name');
            $table->string('key', 36)->unique();
            $table->boolean('requests_allowed');
            $table->timestamp('created_at')->default(new Expression('(NOW())'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space');
    }
};
