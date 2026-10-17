<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The employee profile screen shows a shift ("Morning (09:00 – 18:00)").
     * It was the one field on that page with no column behind it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('shift', 60)->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shift');
        });
    }
};
