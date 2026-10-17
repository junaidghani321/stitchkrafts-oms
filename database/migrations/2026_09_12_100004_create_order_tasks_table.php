<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The `status` column here is the task LIFECYCLE. The SLA status
     * (running / overdue / on_time / late) is never stored — it is derived on
     * every render from assigned_at, duration_hours and completed_at, exactly
     * as the existing views already compute it. Storing it would go stale the
     * moment a deadline passed, and there is no guaranteed queue worker.
     */
    public function up(): void
    {
        Schema::create('order_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Set only when task_type = revision.
            $table->foreignId('order_revision_id')->nullable()->constrained('order_revisions')->nullOnDelete();

            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();

            // digitizing | vector | patch | revision | quality_check
            $table->string('task_type', 20);

            // The "Allowed" column. Minimum of 1 is enforced in validation and
            // guarded again in the calculator — 0 would divide by zero in the
            // view's percent calculation.
            $table->unsignedSmallInteger('duration_hours');

            $table->string('priority', 10)->default('normal');
            $table->text('instructions')->nullable();

            // assigned | in_progress | completed | cancelled
            $table->string('status', 20)->default('assigned')->index();

            $table->dateTime('assigned_at')->index();

            // Denormalised = assigned_at + duration_hours. Stored so "Extend
            // time" can move it while duration_hours stays the honest record
            // of what was originally allowed.
            $table->dateTime('deadline_at')->index();

            $table->dateTime('completed_at')->nullable();
            $table->text('completion_note')->nullable();

            $table->boolean('notify')->default(true);

            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_tasks');
    }
};
