<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A quote is a price enquiry: the same design spec as an order, but asking
     * "what would this cost?" rather than "make this".
     *
     * Its own table rather than a `kind` discriminator on `orders`, because
     * the two have different lifecycles (pending→quoted→approved vs the seven
     * production states) and every existing order scope, policy and query
     * would otherwise need a filter adding — a lot of regression risk on code
     * that is already working. Approving a quote COPIES the spec into a new
     * order and links the two via converted_order_id.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->string('quote_number', 20)->nullable()->unique();
            $table->string('type', 20)->index();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('design_name', 191);
            $table->text('instructions')->nullable();

            // pending | quoted | approved | rejected | expired
            $table->string('status', 20)->default('pending')->index();

            /*
             | Pricing. Null until someone quotes it — the listing renders a
             | greyed-out $ 0.00 in the meantime, same as orders.
             */
            $table->decimal('price', 10, 2)->nullable();
            $table->foreignId('priced_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('priced_at')->nullable();

            // Whoever worked the numbers — drives the employee "Quotes I
            // Prepared" screen.
            $table->foreignId('prepared_by_id')->nullable()->constrained('users')->nullOnDelete();

            // A price cannot stand forever.
            $table->date('valid_until')->nullable();

            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Set once the customer accepts and the quote becomes real work.
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();

            /*
             | The design spec — identical to `orders`, because the quote forms
             | are field-for-field the same as the order forms. Approving
             | copies these across verbatim.
             */
            $table->string('time_frame', 20)->nullable();
            $table->string('required_format', 10)->nullable();

            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->unsignedSmallInteger('colors')->nullable();

            $table->string('unit', 10)->nullable();
            $table->string('fabric', 100)->nullable();
            $table->string('placement', 30)->nullable();
            $table->boolean('puff_3d')->nullable();
            $table->boolean('applique')->nullable();
            $table->boolean('trims')->nullable();
            $table->boolean('patch_cost')->nullable();

            $table->string('color_type', 20)->nullable();
            $table->string('printing_process', 20)->nullable();

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

        // The mock data runs to ADDQ-118, so carry on from there.
        DB::statement('ALTER TABLE quotes AUTO_INCREMENT = 119');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
