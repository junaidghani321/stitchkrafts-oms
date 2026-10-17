<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The audit trail behind the order detail timeline.
     */
    public function up(): void
    {
        Schema::create('order_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // order.created, order.priced, order.status_changed, task.assigned,
            // task.extended, task.completed, revision.requested, file.uploaded ...
            $table->string('event', 40)->index();

            // Pre-rendered sentence, so the timeline renders without having to
            // interpret the properties payload.
            $table->string('description', 255)->nullable();

            // The task / revision / file the event concerns.
            $table->nullableMorphs('subject');

            // Write-once, read-as-a-blob audit payload: {"old":"pending","new":"assigned"}.
            // A deliberate exception to keeping business data in real columns.
            $table->json('properties')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_activities');
    }
};
