<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gives an order message an optional revision facet, so a request for
     * change is a message rather than a row in a parallel table. Replaces
     * order_revisions, which 2026_10_13_140001 backfills and drops.
     *
     * Deliberately carries no assignee, duration or deadline — exactly as
     * order_revisions did not. Those come from an order_tasks row with
     * task_type = revision, so there is one SLA engine and one answer to
     * "who is working on this".
     *
     * Purely additive: nothing reads these columns until the model and
     * service work lands, so this migration is safe to run on its own.
     */
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            // comment | revision. Never set from user input — see MessageKind.
            $table->string('kind', 20)->default('comment')->after('user_id');

            /*
             | The reply that resolved or rejected a revision points at it.
             | nullOnDelete, not cascade: deleting a reply must not take the
             | revision, and deleting a revision must not take a real message
             | somebody wrote. Only ever set on a reply to a kind = revision
             | row — MySQL CHECK cannot express that, so the service enforces it.
             */
            $table->foreignId('parent_id')->nullable()->after('kind')
                ->constrained('order_messages')->nullOnDelete();

            /*
             | The revision facet. All nullable: a comment carries none of it.
             | 24 chars because the number is per-order — ADDO-1234567-R12 is
             | 16, where the old global REV-0031 scheme fitted in 12.
             */
            $table->string('revision_number', 24)->nullable()->unique();

            // customer | quality_check — renders as the `by` column.
            $table->string('source', 20)->nullable();

            // size | colors | stitch | format | artwork | other
            $table->string('reason', 20)->nullable();

            $table->string('priority', 10)->nullable();

            // pending | in_progress | completed | rejected
            // NOTE: deliberately NOT the order vocabulary (progress/done) —
            // oms-table.js matches data-status by exact string equality.
            $table->string('status', 20)->nullable();

            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();

            /*
             | Two composites matched to the two real query shapes. A standalone
             | `kind` index would be a redundant left-prefix of the second.
             | The existing ['order_id', 'created_at'] still serves the thread.
             */
            $table->index(['order_id', 'kind', 'status']);  // the settle interlock
            $table->index(['kind', 'created_at']);          // the /revisions pages
        });

        Schema::table('order_tasks', function (Blueprint $table) {
            /*
             | Replaces order_revision_id, which 2026_10_13_140001 drops once
             | it has copied the rows across. Added here rather than there so
             | the model layer can reference the relation while this migration
             | stays purely additive and reversible.
             */
            $table->foreignId('order_message_id')->nullable()->after('order_revision_id')
                ->constrained('order_messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_message_id');
        });

        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'kind', 'status']);
            $table->dropIndex(['kind', 'created_at']);

            $table->dropConstrainedForeignId('parent_id');

            $table->dropUnique(['revision_number']);
            $table->dropColumn([
                'kind', 'revision_number', 'source', 'reason',
                'priority', 'status', 'due_date', 'completed_at',
            ]);
        });
    }
};
