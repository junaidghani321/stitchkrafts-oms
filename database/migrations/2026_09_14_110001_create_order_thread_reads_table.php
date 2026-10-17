<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How far through a thread each person has read.
     *
     * A high-water mark per (order, user) rather than a read row per message:
     * unread then costs one comparison instead of a NOT EXISTS against every
     * message, and marking a thread read is a single upsert however long it is.
     */
    public function up(): void
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

    public function down(): void
    {
        Schema::dropIfExists('order_thread_reads');
    }
};
