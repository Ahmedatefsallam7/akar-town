<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 191);
            $table->string('name_ar', 191);
            $table->string('slug', 191)->unique();
            // A project with units can't be deleted (also checked in the admin, with a reason).
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();

            // Single choices from Lookups (filtered on the website, so real indexed columns).
            foreach (['offering_type_id', 'property_type_id', 'property_usage_id', 'finishing_type_id', 'furnishing_status_id', 'unit_status_id'] as $column) {
                $table->foreignId($column)->nullable()->constrained('lookups')->nullOnDelete();
            }

            // Location: a point in the location chain (filled from the project when empty).
            $table->foreignId('country_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->string('neighbourhood', 191)->nullable();
            $table->string('address', 191)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('order')->default(0);

            // Pricing & space: price, area, bedrooms and bathrooms drive the website search filters.
            $table->unsignedBigInteger('price')->nullable()->index();
            $table->unsignedInteger('area')->nullable()->index();
            $table->unsignedTinyInteger('bedrooms')->nullable()->index();
            $table->unsignedTinyInteger('bathrooms')->nullable()->index();
            $table->smallInteger('floor')->nullable();
            $table->decimal('down_payment', 5, 2)->nullable();
            $table->unsignedTinyInteger('installment_years_from')->nullable();
            $table->unsignedTinyInteger('installment_years_to')->nullable();
            $table->date('delivery_date')->nullable();

            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('description_ar')->nullable();

            // Shared media pack; a gallery that units use can't be deleted.
            $table->foreignId('gallery_id')->nullable()->constrained('galleries')->restrictOnDelete();
            $table->string('video_url', 500)->nullable();

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

            // Website: published units, featured ones in "Our Latest Properties", in display order. Admin: newest first.
            $table->index(['is_published', 'is_featured', 'order']);
            $table->index(['deleted_at', 'created_at']);
            // Fast "starts with" name search (PrefixSearch); the slug is already unique.
            $table->index('name_en');
            $table->index('name_ar');
        });

        // Every price a unit had, newest first on its page.
        Schema::create('unit_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unsignedBigInteger('price');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['unit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_price_histories');
        Schema::dropIfExists('units');
    }
};
