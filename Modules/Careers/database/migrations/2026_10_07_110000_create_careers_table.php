<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Job openings for the website's careers page. Benefits (from Lookups) are in "lookupables".
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 191);
            $table->string('title_ar', 191);
            $table->string('slug', 191)->unique();
            $table->string('type', 20);
            $table->unsignedSmallInteger('vacancies');
            $table->foreignId('department_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->date('closing_date')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('country_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('sub_area_id')->nullable()->constrained('lookups')->nullOnDelete();

            $table->longText('description_en')->nullable();
            $table->longText('description_ar')->nullable();
            // Bullet lists, each item [en => …, ar => …].
            $table->json('responsibilities')->nullable();
            $table->json('requirements')->nullable();

            $table->boolean('show_salary')->default(false);
            $table->unsignedInteger('salary_from')->nullable();
            $table->unsignedInteger('salary_to')->nullable();
            $table->string('salary_currency', 3)->nullable();

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

            // The website lists open jobs (published, not past their closing date); the admin list is newest first.
            $table->index(['is_active', 'closing_date']);
            $table->index(['deleted_at', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
