<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The developer page's "Follow us on" links (the design's developer-details.html).
     */
    public function up(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->string('social_facebook')->nullable()->after('neighbourhood');
            $table->string('social_instagram')->nullable()->after('social_facebook');
            $table->string('social_linkedin')->nullable()->after('social_instagram');
            $table->string('social_youtube')->nullable()->after('social_linkedin');
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn(['social_facebook', 'social_instagram', 'social_linkedin', 'social_youtube']);
        });
    }
};
