<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_out_id'); // FK to master_stock_out
            $table->unsignedBigInteger('product_id');   // FK to products
            $table->unsignedBigInteger('unit_id');      // FK to units
            $table->unsignedBigInteger('location_id');  // from which location it was taken
            $table->unsignedBigInteger('stock_purchase_item_id')->nullable(); // FK to purchase batch

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('stock_out_id')
                  ->references('id')
                  ->on('master_stock_out')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('unit_id')
                  ->references('id')
                  ->on('units')
                  ->onDelete('cascade');

            $table->foreign('location_id')
                  ->references('id')
                  ->on('locations')
                  ->onDelete('cascade');

            $table->foreign('stock_purchase_item_id')
                  ->references('id')
                  ->on('stock_purchase_items')
                  ->onDelete('set null'); // keep history even if purchase is deleted
        });
    }

    public function down(): void {
        Schema::dropIfExists('stock_out_items');
    }
};
