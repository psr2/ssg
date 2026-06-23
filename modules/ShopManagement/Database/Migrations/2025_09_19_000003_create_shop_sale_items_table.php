<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('shop_sales')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id'); // link to stock/warehouse
            $table->string('product_name'); // snapshot 
            $table->integer('grade'); //link to grade table
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_sale_items');
    }
};
