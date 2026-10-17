<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An invoice bills a customer for delivered work.
     *
     * One invoice covers many orders — the billing screen batches "delivered
     * but not yet invoiced" orders into a single bill per customer, which is
     * why payment is taken against an invoice rather than an order.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // INV-2026-042, stamped by the observer from the primary key.
            $table->string('invoice_number', 20)->nullable()->unique();

            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('issued_date');
            $table->date('due_date')->nullable();

            /*
             | Stored in the smallest currency unit (cents) as an integer.
             |
             | Money in floats is how you end up billing 49.99 as 4998. Every
             | total here is an integer; the views divide by 100 for display.
             */
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('paid_cents')->default(0);

            $table->string('currency', 3)->default('usd');

            // unpaid | partial | paid | refunded — the same enum orders use.
            $table->string('payment_status', 20)->default('unpaid')->index();

            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'payment_status']);
            $table->index(['issued_date']);
        });

        // The mock data runs to INV-2026-041.
        DB::statement('ALTER TABLE invoices AUTO_INCREMENT = 42');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
