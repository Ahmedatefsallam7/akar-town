<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The homepage blocks: a fixed set made by the seeder. Admins edit, switch on/off and re-order; none are added or deleted.
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 191);
            $table->string('title_ar', 191);
            $table->string('placement', 20)->default('home_body');
            $table->string('content_type', 20)->default('projects');
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // The website reads the active blocks of a placement in display order.
            $table->index(['placement', 'is_active', 'order']);
        });

        // Hero slides: each shows one project (its title, description and image) on the homepage hero.
        Schema::create('slider_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slider_id')->constrained('sliders')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['slider_id', 'project_id']);
            $table->index(['slider_id', 'is_active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider_items');
        Schema::dropIfExists('sliders');
    }
};
