<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Image sizes changed by an admin (AKAR-50): one row per changed field or place, only the values that differ from
 * the design's default; "back to default" deletes the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('values');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_sizes');
    }
};
