<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per payment recorded against an invoice.
     *
     * Payments are taken outside the system — bank transfer, cash, cheque —
     * and entered here by an admin. Keeping a row per entry rather than a
     * flag on the invoice is what lets you reconcile, part-pay, or answer
     * "what actually happened" months later.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();

            // manual — the only method; kept as a column so another can be
            // added later without reshaping the table.
            $table->string('method', 20)->default('manual')->index();

            // pending | succeeded | failed | refunded
            $table->string('status', 20)->default('succeeded')->index();

            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('usd');

            $table->string('reference', 100)->nullable();

            // Free-form detail kept verbatim for reconciliation.
            $table->json('payload')->nullable();

            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
