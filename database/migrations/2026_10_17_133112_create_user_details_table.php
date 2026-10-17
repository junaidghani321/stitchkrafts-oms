<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The customer-facing profile that hangs off a user.
     *
     * A satellite table rather than more columns on `users`: these fields
     * belong to the `user` role only, and the auth table is already read on
     * every request by the session guard. One row per user, so `user_id` is
     * unique and the relation is a hasOne.
     *
     * `country` is a char(2) holding the Country enum's ISO code. The codes are
     * spelled out here rather than read from the enum — a migration is a
     * historical snapshot, and a fourth country must arrive as its own
     * migration instead of silently changing what this one creates. Blueprint
     * has no check() helper, hence DB::statement.
     */
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('username', 50)->unique();
            $table->char('country', 2);
            $table->string('company_name', 150)->nullable();
            $table->string('contact_number', 30)->nullable();

            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE user_details
              ADD CONSTRAINT user_details_country_allowed
              CHECK (country IN ('au', 'uk', 'us'))
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Dropping the table takes its constraint with it, so there is nothing to
     * unwind first.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
