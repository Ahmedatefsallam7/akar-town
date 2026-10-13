<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // The point in the location chain (lookups under "locations"); every level above it is filled too.
            $table->foreignId('country_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('lookups')->nullOnDelete();

            $table->string('name_en', 191);
            $table->string('name_ar', 191);
            $table->string('slug', 191)->unique();
            $table->string('code', 10)->nullable();
            $table->json('neighbourhoods')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

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

            // The website lists active locations, featured first, in display order; the admin list is newest first.
            $table->index(['is_active', 'is_featured', 'order']);
            $table->index(['deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
