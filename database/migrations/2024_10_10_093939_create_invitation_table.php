<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitation', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('space_id')->references('id')->on('space');
            $table->string('user_id', 40);
            $table->foreignId('role_id')->references('id')->on('role');
            $table->timestamp('created_at')->default(new Expression('(NOW())'));
            $table->index(['space_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation');
    }
};
