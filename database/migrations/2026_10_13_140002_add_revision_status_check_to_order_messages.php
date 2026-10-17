<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Closes the one way the merged schema can drift — a kind = revision row
     * with no status — and drops the mapping column 2026_10_13_140001 used.
     *
     * Runs after the backfill rather than with the column definitions so a
     * buggy backfill is caught by this constraint rather than blocked by it.
     *
     * Note the implication, not a biconditional: "a comment must have a NULL
     * status" would be wrong the first time a third MessageKind is added.
     * Blueprint has no check() helper, hence DB::statement.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE order_messages
              ADD CONSTRAINT order_messages_revision_has_status
              CHECK (kind <> 'revision' OR status IS NOT NULL)
        ");

        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropIndex(['legacy_revision_id']);
            $table->dropColumn('legacy_revision_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_revision_id')->nullable()->index();
        });

        DB::statement('ALTER TABLE order_messages DROP CONSTRAINT order_messages_revision_has_status');
    }
};
