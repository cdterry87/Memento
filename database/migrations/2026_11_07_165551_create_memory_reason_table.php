<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('memory_reason', function (Blueprint $table) {
            $table->foreignId('memory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reason_id')->constrained()->restrictOnDelete();

            $table->primary(['memory_id', 'reason_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_reason');
    }
};
