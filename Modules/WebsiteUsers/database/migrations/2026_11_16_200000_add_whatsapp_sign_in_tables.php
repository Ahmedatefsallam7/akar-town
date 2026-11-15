<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp sign-in (AKAR-49): accounts made with a WhatsApp number have no e-mail; one current code per number
 * (hashed, its expiry, wrong tries since the last right one); and every lock of a number after 3 wrong codes, kept
 * as a log with who unlocked it and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_users', function (Blueprint $table) {
            $table->string('email', 191)->nullable()->change();
        });

        Schema::create('website_phone_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedTinyInteger('wrong_attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('website_phone_locks', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->foreignId('website_user_id')->nullable()->constrained('website_users')->nullOnDelete();
            $table->unsignedTinyInteger('attempts');
            $table->string('ip', 45)->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['phone', 'unlocked_at']);
            $table->index(['unlocked_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_phone_locks');
        Schema::dropIfExists('website_phone_codes');
    }
};
