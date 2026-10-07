<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People who register on the public website. A separate table (and login guard) from admin users, so a
        // website account can never get admin access.
        Schema::create('website_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            // E-mail is required and unique; the mobile number is unique (team lead, 2026-10-07).
            $table->string('email', 191)->unique();
            $table->string('phone', 30)->nullable()->unique();
            $table->string('avatar', 255)->nullable();
            $table->string('registration_method', 20)->default('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('preferred_language', 2)->default('en');
            // Never shown or changed in the admin.
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // The list is newest first, filtered by status / registration date, sortable by last login; search by how the name starts.
            $table->index(['deleted_at', 'created_at']);
            $table->index('status');
            $table->index('name');
            $table->index('last_login_at');
        });

        // Leads sent while logged in point at the account (guests stay unlinked).
        foreach (['inquiries', 'meetings', 'unit_requests'] as $leads) {
            Schema::table($leads, function (Blueprint $table) {
                $table->foreignId('website_user_id')->nullable()->after('id')->constrained('website_users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['inquiries', 'meetings', 'unit_requests'] as $leads) {
            Schema::table($leads, fn (Blueprint $table) => $table->dropConstrainedForeignId('website_user_id'));
        }

        Schema::dropIfExists('website_users');
    }
};
