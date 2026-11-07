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
        Schema::create('memory_photos', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('memory_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size_bytes');
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->index(['memory_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_photos');
    }
};
