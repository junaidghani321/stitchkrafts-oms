<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One polymorphic table for every file in the module. Customer artwork,
     * employee deliverables, revision attachments and revision deliverables
     * have identical columns, so they share a table, a service, a download
     * route and a policy. `category` is what keeps them apart.
     */
    public function up(): void
    {
        Schema::create('order_files', function (Blueprint $table) {
            $table->id();

            // Order, OrderMessage or Quote. morphs() indexes the pair.
            $table->morphs('attachable');

            // artwork | deliverable | revision_request | revision_deliverable
            $table->string('category', 24)->index();

            $table->string('disk', 20)->default('orders');

            // ULID-based path on a private disk; never web-served.
            $table->string('path', 255);

            // What the customer sees on download.
            $table->string('original_name', 255);

            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id', 'category'], 'order_files_attachable_category_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_files');
    }
};
