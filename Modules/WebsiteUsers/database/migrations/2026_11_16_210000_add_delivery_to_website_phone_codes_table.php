<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether the WhatsApp code really arrived (AKAR-49): Meta's id for the message and its delivery status from Meta's
 * webhook (sent → delivered → read, or failed with Meta's error code, e.g. 131026: the number has no WhatsApp);
 * and the codes sent this hour, counted here so a code that never arrived can be given back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_phone_codes', function (Blueprint $table) {
            $table->string('message_id', 191)->nullable()->unique()->after('sent_at');
            $table->string('delivery', 20)->nullable()->after('message_id');
            $table->string('delivery_error', 20)->nullable()->after('delivery');
            $table->unsignedTinyInteger('sends')->default(0)->after('delivery_error');
            $table->timestamp('sends_since')->nullable()->after('sends');
        });
    }

    public function down(): void
    {
        Schema::table('website_phone_codes', function (Blueprint $table) {
            $table->dropUnique(['message_id']);
            $table->dropColumn(['message_id', 'delivery', 'delivery_error', 'sends', 'sends_since']);
        });
    }
};
