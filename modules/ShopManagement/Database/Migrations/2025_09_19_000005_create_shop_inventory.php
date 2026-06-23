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
        Schema::create('shop_inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');             // which shop holds the stock


            $table->string('batch_id');   // link to warehouse batch
            $table->integer('grade')->default(null);              
            $table->unsignedBigInteger('product_id');      // product/item reference

            $table->decimal('qty', 12, 2)->default(0);     // current available qty in shop
            $table->decimal('unit_cost', 12, 2)->nullable();   // cost of that batch (from warehouse)
            $table->timestamps();

            // Foreign keys 
            $table->foreign('shop_id')->references('id')->on('locations');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('batch_id')->references('batch')->on('stock_purchase_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_inventory');
    }
};
