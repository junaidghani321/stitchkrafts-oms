<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The conversation feature is gone, and this table was all of its state:
     * a high-water mark per (order, user) for "how far through the thread
     * have you read".
     *
     * order_messages stays. A revision is one of its rows, and the work
     * handed in against a revision hangs off a reply — nothing of that is
     * chat.
     */
    public function up(): void
    {
        Schema::dropIfExists('order_thread_reads');
    }

    /**
     * Recreated empty. Read marks are not reconstructible once dropped, and
     * nothing reads them any more.
     */
    public function down(): void
    {
        Schema::create('order_thread_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('last_read_at');

            $table->timestamps();

            $table->unique(['order_id', 'user_id']);
        });
    }
};
