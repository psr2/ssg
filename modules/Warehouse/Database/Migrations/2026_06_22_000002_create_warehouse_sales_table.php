<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warehouse sales master table.
     * Each row is a sale transaction from a warehouse to a customer.
     */
    public function up(): void
    {
        Schema::create('warehouse_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('warehouse_customers')
                ->nullOnDelete();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('sale_date');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_sales');
    }
};
