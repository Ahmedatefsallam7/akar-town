<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 191);
            $table->string('name_ar', 191);
            $table->string('slug', 191)->unique();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('tagline_en', 191)->nullable();
            $table->string('tagline_ar', 191)->nullable();
            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            // Contact and head office: a point in the location chain (lookups under "locations") + a neighbourhood.
            $table->string('email', 191)->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('country_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->string('neighbourhood', 191)->nullable();

            $table->string('meta_title_en', 191)->nullable();
            $table->string('meta_title_ar', 191)->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->json('meta_keywords_en')->nullable();
            $table->json('meta_keywords_ar')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // The website lists published developers (featured ones in the partners slider); the admin list is newest first.
            $table->index(['is_published', 'is_featured']);
            $table->index(['deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developers');
    }
};
