<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who signed the finished work off, and when.
     *
     * An employee finishing their task now parks the order at
     * `pending_approval` instead of `completed`; an admin or manager approves
     * it before the customer is told anything is ready. `completed_at` records
     * when the order finished, which is not the same question as who allowed
     * it to. Mirrors the priced_by_id / priced_at and cancelled_by_id /
     * cancelled_at pairs already on this table.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('approved_by_id')->nullable()->after('cancellation_reason')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('approved_by_id');
            $table->string('approval_note', 500)->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropColumn(['approved_at', 'approval_note']);
        });
    }
};
