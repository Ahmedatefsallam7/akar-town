<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A logged-in user's wishlist: one row per saved unit or project (guests keep theirs in the browser). Deleting
     * the user, unit or project deletes its rows; the same thing can't be saved twice.
     */
    public function up(): void
    {
        Schema::create('website_user_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_user_id')->constrained('website_users')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['website_user_id', 'unit_id']);
            $table->unique(['website_user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_user_favourites');
    }
};
