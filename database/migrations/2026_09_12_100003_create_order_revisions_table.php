<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deliberately carries no assignee, duration or deadline. The employee
     * revisions view needs all three, but they come from an order_tasks row
     * with task_type = revision — so there is exactly one SLA engine and one
     * answer to "who is working on this".
     */
    public function up(): void
    {
        Schema::create('order_revisions', function (Blueprint $table) {
            $table->id();

            // REV-0031, stamped by the observer from the primary key.
            $table->string('revision_number', 12)->nullable()->unique();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();

            // customer | quality_check — renders as the `by` column.
            $table->string('source', 20)->default('customer');

            // size | colors | stitch | format | artwork | other
            $table->string('reason', 20);

            $table->string('priority', 10)->default('normal');

            // pending | in_progress | completed | rejected
            // NOTE: deliberately NOT the order vocabulary (progress/done) —
            // oms-table.js matches data-status by exact string equality.
            $table->string('status', 20)->default('pending')->index();

            $table->date('due_date')->nullable();
            $table->text('comments');

            $table->dateTime('completed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // Match the existing mock data, which starts at REV-0031.
        DB::statement('ALTER TABLE order_revisions AUTO_INCREMENT = 31');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_revisions');
    }
};
