<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 191)->nullable();
            $table->string('name_ar', 191)->nullable();
            $table->integer('order')->nullable();
            $table->string('slug', 191)->index();
            $table->json('attributes')->nullable();
            $table->mediumText('description_en')->nullable();
            $table->mediumText('description_ar')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_deletable')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->nestedSet();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookups');
    }
};
