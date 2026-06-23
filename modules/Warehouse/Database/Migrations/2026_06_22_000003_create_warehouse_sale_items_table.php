<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Line items for each warehouse sale.
     * Each row is one product/batch sold in a sale.
     */
    public function up(): void
    {
        Schema::create('warehouse_sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name')->default('');
            $table->string('batch_code');
            $table->integer('grade')->default(1);
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();

            $table->foreign('sale_id')
                ->references('id')
                ->on('warehouse_sales')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');

            $table->foreign('batch_code')
                ->references('batch')
                ->on('stock_purchase_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_sale_items');
    }
};
