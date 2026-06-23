<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');       // which warehouse holds the stock
            $table->string('batch');           // link to stock purchase item
            $table->unsignedBigInteger('product_id');      

            // Todo - batch id was added later , check for integrity issues once
            $table->string('grade'); 
            
            // product/item reference
            $table->decimal('qty', 12, 2)->default(0);        // current available qty in warehouse
            $table->decimal('unit_cost', 12, 2)->nullable();  // cost of that batch

            $table->timestamps();

            // Foreign keys
            $table->foreign('warehouse_id')->references('id')->on('locations');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('batch')->references('batch')->on('stock_purchase_items');

            // Create an index for batch in warehouse_inventory for performance
            $table->index('batch');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory');
    }
};
