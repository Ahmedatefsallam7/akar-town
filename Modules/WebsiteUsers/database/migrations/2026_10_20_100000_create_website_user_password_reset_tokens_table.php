<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Forgot password" links of website users. Separate from the admins' table (keyed by e-mail): an admin and a
     * website user with the same e-mail must not overwrite each other's link.
     */
    public function up(): void
    {
        Schema::create('website_user_password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_user_password_reset_tokens');
    }
};
