<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who cancelled an order, when, and why.
     *
     * `status = cancelled` alone answers none of those, and "why did this
     * order stop?" is the first question asked months later. Mirrors the
     * priced_by_id / priced_at pair already on this table.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cancelled_by_id')->nullable()->after('delivered_at')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable()->after('cancelled_by_id');
            $table->string('cancellation_reason', 500)->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by_id');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
