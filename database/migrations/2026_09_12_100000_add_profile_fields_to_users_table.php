<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Only `status` is load-bearing for the order module — a deactivated
     * employee must not appear in the assign-task dropdown. The rest are
     * columns the users/employees screens already display; adding them in the
     * same ALTER is cheaper than a second pass over the auth table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 10)->default('active')->after('type');
            $table->string('designation', 100)->nullable()->after('status');
            $table->string('department', 100)->nullable()->after('designation');
            $table->string('phone', 30)->nullable()->after('department');
            $table->string('company', 150)->nullable()->after('phone');
            $table->text('notes')->nullable()->after('company');

            // Every customer/employee lookup filters on one or both of these.
            $table->index('type', 'users_type_index');
            $table->index(['type', 'status'], 'users_type_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_type_status_index');
            $table->dropIndex('users_type_index');

            $table->dropColumn([
                'status',
                'designation',
                'department',
                'phone',
                'company',
                'notes',
            ]);
        });
    }
};
