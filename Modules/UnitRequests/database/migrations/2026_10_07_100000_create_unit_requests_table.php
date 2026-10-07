<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Units owners submit for sale on the website ("Submit Your Property"); staff review them and may turn one into
        // a property. Picked lookup values (amenities, offering types…) are in "lookupables".
        Schema::create('unit_requests', function (Blueprint $table) {
            $table->id();
            $table->string('seller_name', 191);
            $table->string('email', 191)->nullable();
            $table->string('phone', 30);

            $table->string('name_en', 191);
            $table->string('name_ar', 191)->nullable();
            $table->foreignId('country_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->string('address', 191)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedInteger('area')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('down_payment', 5, 2)->nullable();
            $table->unsignedTinyInteger('installment_years_from')->nullable();
            $table->unsignedTinyInteger('installment_years_to')->nullable();

            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('description_ar')->nullable();

            $table->string('status', 20)->default('pending');
            $table->text('internal_notes')->nullable();
            // The property made from this request, if any.
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // The list is newest first, filtered by status, sorted / filtered by price; search by how name / phone / email start.
            $table->index(['deleted_at', 'created_at']);
            $table->index('status');
            $table->index('price');
            $table->index('seller_name');
            $table->index('name_en');
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_requests');
    }
};
