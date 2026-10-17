<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One line per order on an invoice.
     *
     * The unique index on order_id is the mechanism that makes "delivered but
     * not yet invoiced" work, and the thing that stops an order being billed
     * twice — a race between two admins clicking Generate at the same moment
     * fails at the database rather than double-charging a customer.
     *
     * description and amount are COPIED, not read through the relation: an
     * invoice is a financial record of what was agreed at the time, so later
     * edits to the order must not silently rewrite history.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();

            $table->string('description', 255);
            $table->unsignedBigInteger('amount_cents');

            $table->timestamps();

            // An order can appear on exactly one invoice.
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
