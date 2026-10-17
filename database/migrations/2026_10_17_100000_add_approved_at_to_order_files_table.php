<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When an approver signed a deliverable off, or NULL while it is still
     * waiting. Only the customer's view is gated on it — staff see everything
     * they are about to approve.
     *
     * Deliberately per-file rather than reading the parent order's status. A
     * delivered order that goes back into revision returns to PendingApproval,
     * and an order-level gate would hide the files the customer already has
     * for as long as the new work sits unapproved. Per-file, the previously
     * approved deliverables stay visible and only the new upload is withheld.
     */
    public function up(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->dateTime('approved_at')->nullable()->after('uploaded_by_id');

            // The customer's deliverables list: category + approved_at.
            $table->index(['category', 'approved_at']);
        });

        /*
         | Backfill so nothing a customer can already see disappears.
         |
         | Everything except files on an order that is awaiting approval right
         | now counts as approved: those files are visible today, and this
         | change is not meant to retract them. Files on a PendingApproval
         | order are exactly the ones the new rule is about, so they stay NULL
         | and become visible when the approver signs off.
         */
        DB::statement('
            UPDATE order_files f
            JOIN orders o ON o.id = f.attachable_id AND f.attachable_type = ?
            SET f.approved_at = COALESCE(o.approved_at, o.completed_at, f.created_at)
            WHERE f.category IN (?, ?)
              AND o.status <> ?
        ', ['App\Models\Order', 'deliverable', 'revision_deliverable', 'pending_approval']);

        /*
         | Revision deliverables hang off the OrderMessage they were handed in
         | with, not off the order, so they need their own pass through it.
         */
        DB::statement('
            UPDATE order_files f
            JOIN order_messages m ON m.id = f.attachable_id AND f.attachable_type = ?
            JOIN orders o ON o.id = m.order_id
            SET f.approved_at = COALESCE(o.approved_at, o.completed_at, f.created_at)
            WHERE f.category IN (?, ?)
              AND o.status <> ?
        ', ['App\Models\OrderMessage', 'deliverable', 'revision_deliverable', 'pending_approval']);
    }

    public function down(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->dropIndex(['category', 'approved_at']);
            $table->dropColumn('approved_at');
        });
    }
};
