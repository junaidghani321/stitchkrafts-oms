<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users are soft-deleted, not removed.
     *
     * orders.customer_id and order_tasks.employee_id are both restrictOnDelete,
     * so a hard delete of anyone who has ever placed or worked an order throws
     * a foreign key error. Soft deleting matches what the delete dialog already
     * promises — "their orders stay in the system" — and keeps every historical
     * record pointing at a real name.
     *
     * Laravel's Eloquent user provider applies model global scopes, so a
     * soft-deleted user can no longer authenticate and is signed out on their
     * next request.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
