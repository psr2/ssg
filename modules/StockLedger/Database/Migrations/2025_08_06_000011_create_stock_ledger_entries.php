<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger_entries', function (Blueprint $table) {
            $table->id();

            // Type of event/transaction
            $table->string('transaction_type'); // e.g., PURCHASE, TRANSFER_OUT, TRANSFER_IN, SEGREGATION_OUT, SEGREGATION_IN, SALE, STOCK_OUT, ADJUSTMENT

            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('product_id');
            $table->string('batch_code');
            $table->string('grade')->nullable();
            
            // Signed delta quantity (positive for additions, negative for deductions)
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('unit_cost', 10, 2)->default(0.00);

            // Polymorphic relation to source record (e.g. stock_purchase_item_id, stock_transfer_id, etc.)
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable();

            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('location_id')
                ->references('id')->on('locations')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger_entries');
    }
};
