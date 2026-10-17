<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The morph class as it is STORED, not as it is spelled in code.
     *
     * No morph map is registered (AppServiceProvider::boot() is empty), so
     * attachable_type / subject_type hold the literal FQCN. Deliberately a
     * string and not OrderRevision::class: that class is deleted in this same
     * release, so referencing it would fatal on any later `migrate`.
     */
    private const LEGACY_MORPH = 'App\Models\OrderRevision';

    private const MESSAGE_MORPH = 'App\Models\OrderMessage';

    /**
     * Moves every order_revisions row into order_messages as a kind = revision
     * message, re-points everything that referenced a revision, and drops the
     * table.
     *
     * Set-based throughout, via a throwaway mapping column, so it handles any
     * row count and leaves the old-id -> new-id map visible in the database if
     * it fails part way. 2026_10_13_140002 drops that column.
     *
     * Atomic on purpose: between the backfill and the drop, two tables hold the
     * same truth and the settle interlock could read the wrong one.
     */
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_revision_id')->nullable()->index();
        });

        DB::transaction(function () {
            // 1. The revisions themselves. comments -> body,
            //    requested_by_id -> user_id, revision_number -> revision_number.
            //    created_at is carried across so the thread still orders right.
            DB::statement('
                INSERT INTO order_messages
                    (order_id, user_id, body, kind, revision_number, source, reason,
                     priority, status, due_date, completed_at, legacy_revision_id,
                     created_at, updated_at)
                SELECT order_id, requested_by_id, comments, ?, revision_number, source,
                       reason, priority, status, due_date, completed_at,
                       id, created_at, updated_at
                FROM order_revisions
            ', ['revision']);

            /*
             | 2. resolution_note and rejection_reason become replies, because
             |    the merged model has no column for them. This is the only step
             |    that would otherwise destroy information, so it runs even
             |    though the seeder never populated either field.
             |
             |    user_id is NULL: the old schema never recorded who wrote the
             |    note, and inventing an author would be worse than "Removed
             |    user". completed_at is the best timestamp available.
             */
            foreach (['resolution_note', 'rejection_reason'] as $column) {
                DB::statement("
                    INSERT INTO order_messages
                        (order_id, user_id, body, kind, parent_id, created_at, updated_at)
                    SELECT r.order_id, NULL, r.{$column}, ?, m.id,
                           COALESCE(r.completed_at, r.updated_at), r.updated_at
                    FROM order_revisions r
                    JOIN order_messages m ON m.legacy_revision_id = r.id
                    WHERE r.{$column} IS NOT NULL AND r.{$column} <> ''
                ", ['comment']);
            }

            // 3. The task that carries the work.
            DB::statement('
                UPDATE order_tasks t
                JOIN order_messages m ON m.legacy_revision_id = t.order_revision_id
                SET t.order_message_id = m.id
                WHERE t.order_revision_id IS NOT NULL
            ');

            /*
             | 4. Files. attachable_id and attachable_type MUST move in one
             |    statement. Setting the id first would break the next
             |    statement's WHERE; setting the type first would make the id
             |    join match the wrong rows. The type predicate appears in both
             |    the ON and the WHERE so the join cannot collide with an
             |    Order-attached file whose order id equals a revision id.
             */
            DB::statement('
                UPDATE order_files f
                JOIN order_messages m
                  ON m.legacy_revision_id = f.attachable_id
                 AND f.attachable_type = ?
                SET f.attachable_id = m.id,
                    f.attachable_type = ?
                WHERE f.attachable_type = ?
            ', [self::LEGACY_MORPH, self::MESSAGE_MORPH, self::LEGACY_MORPH]);

            // 5. Activity subjects, same shape and the same two hazards. The
            //    `event` strings are display/tone keys, not references, so
            //    revision.requested and friends are left exactly as they are.
            DB::statement('
                UPDATE order_activities a
                JOIN order_messages m
                  ON m.legacy_revision_id = a.subject_id
                 AND a.subject_type = ?
                SET a.subject_id = m.id,
                    a.subject_type = ?
                WHERE a.subject_type = ?
            ', [self::LEGACY_MORPH, self::MESSAGE_MORPH, self::LEGACY_MORPH]);
        });

        // 6. The FK has to go before the table can be dropped. A bare
        //    dropColumn would fail while the constraint exists.
        Schema::table('order_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_revision_id');
        });

        Schema::dropIfExists('order_revisions');
    }

    /**
     * Not reversible, and deliberately loud about it.
     *
     * Restoring the table would mean deleting the reply messages that the
     * resolution notes became — replies that may themselves have been replied
     * to by now — and deciding which reply had been the resolution is a guess
     * even with parent_id. Roll forward from a backup instead.
     *
     * migrate:fresh is unaffected: it never runs down().
     */
    public function down(): void
    {
        throw new RuntimeException(
            'Not reversible: order_revisions was merged into order_messages. Restore from backup.',
        );
    }
};
