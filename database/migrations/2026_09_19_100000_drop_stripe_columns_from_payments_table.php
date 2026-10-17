<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the Stripe columns.
     *
     * Payments are no longer taken through the system; an admin records them
     * by hand. The create migration no longer adds these columns, so the
     * guards keep this a no-op on a freshly migrated database while still
     * cleaning up one that was migrated before the gateway was removed.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['stripe_session_id', 'stripe_payment_intent_id'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    // MySQL drops the column's unique index along with it.
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'stripe_session_id')) {
                $table->string('stripe_session_id', 191)->nullable()->unique();
            }

            if (! Schema::hasColumn('payments', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 191)->nullable()->unique();
            }
        });
    }
};
