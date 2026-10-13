<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 191);
            $table->string('title_ar', 191);
            $table->string('slug', 191)->unique();
            $table->string('project_number', 50)->nullable()->unique();
            // A developer with projects can't be deleted (also checked in the admin, with a reason).
            $table->foreignId('developer_id')->constrained('developers')->restrictOnDelete();

            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('description_ar')->nullable();

            // Location: a point in the location chain (lookups under "locations"), country/city/region required.
            $table->foreignId('country_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->string('neighbourhood', 191)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address_en', 191)->nullable();
            $table->string('address_ar', 191)->nullable();

            // Pricing & finance. Price and area ranges drive the website filters and "starting from" labels.
            $table->foreignId('currency_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->unsignedBigInteger('price_from')->nullable()->index();
            $table->unsignedBigInteger('price_to')->nullable();
            $table->unsignedInteger('area_from')->nullable()->index();
            $table->unsignedInteger('area_to')->nullable();
            $table->decimal('down_payment', 5, 2)->nullable();
            $table->unsignedTinyInteger('installment_years_from')->nullable();
            $table->unsignedTinyInteger('installment_years_to')->nullable();
            $table->date('delivery_date')->nullable()->index();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->text('video_embed')->nullable();

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

            // Website: published projects (featured ones in the homepage slider); admin list: newest first.
            $table->index(['is_published', 'is_featured']);
            $table->index(['deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
