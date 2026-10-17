<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email preferences as JSON on the user.
     *
     * A separate table would buy nothing here: these are read as a whole blob
     * whenever a notification is sent, never queried key-by-key, and there are
     * a handful per user. Same reasoning as order_activities.properties.
     *
     * NULL means "never saved anything" — the defaults in
     * config/notification_preferences.php apply.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
