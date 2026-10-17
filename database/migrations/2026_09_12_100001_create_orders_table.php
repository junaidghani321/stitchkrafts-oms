<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One table for all three order types, with the type-specific columns
     * nullable. Which columns are required is enforced per type in the Form
     * Requests, not in the schema — the three forms genuinely disagree about
     * what is mandatory.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            /*
             | Identity & ownership
             */
            // Nullable only for the instant between INSERT and the observer
            // stamping it; the unique index is the backstop.
            $table->string('order_number', 20)->nullable()->unique();
            $table->string('type', 20)->index();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('design_name', 191);
            $table->text('instructions')->nullable();

            /*
             | The three status axes — work, money, delivery. They move
             | independently: an order can be completed, unpaid and undelivered.
             */
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->string('delivery_status', 20)->default('pending')->index();

            /*
             | Pricing — null until an admin or manager prices it.
             */
            $table->decimal('price', 10, 2)->nullable();
            $table->foreignId('priced_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('priced_at')->nullable();

            /*
             | Dates. due_at is derived at creation from the SLA tier, so the
             | hour-based and day-based tiers converge here and nothing
             | downstream has to branch on type again.
             */
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('delivered_at')->nullable();

            /*
             | Digitizing + vector
             */
            $table->string('time_frame', 20)->nullable();
            $table->string('required_format', 10)->nullable();

            /*
             | Digitizing + patch
             */
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->unsignedSmallInteger('colors')->nullable();

            /*
             | Digitizing only. The booleans are nullable on purpose:
             | null means "not answered", false means "explicitly No".
             */
            $table->string('unit', 10)->nullable();
            $table->string('fabric', 100)->nullable();
            $table->string('placement', 30)->nullable();
            $table->boolean('puff_3d')->nullable();
            $table->boolean('applique')->nullable();
            $table->boolean('trims')->nullable();
            $table->boolean('patch_cost')->nullable();

            /*
             | Vector only
             */
            $table->string('color_type', 20)->nullable();
            $table->string('printing_process', 20)->nullable();

            /*
             | Patch only. expected_delivery is kept separate from time_frame
             | because "Normal" means 15 days here and 24 hours there.
             */
            $table->string('expected_delivery', 20)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('patch_type', 30)->nullable();
            $table->string('patch_background', 30)->nullable();
            $table->string('backing_type', 30)->nullable();
            $table->string('patch_border', 30)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
        });

        // Order numbers are derived from the primary key, so start the
        // sequence where the existing mock data leaves off (ADDO-442 etc).
        DB::statement('ALTER TABLE orders AUTO_INCREMENT = 500');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
