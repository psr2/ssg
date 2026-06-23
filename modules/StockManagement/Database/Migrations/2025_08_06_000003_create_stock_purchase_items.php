<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_in_purchase_id'); // FK to stock_purchase
            $table->unsignedBigInteger('location_id'); // NEW: item-specific location
            $table->string('product');
            $table->string('batch');
            $table->string('grade');
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 12, 2);
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('stock_in_purchase_id')
                ->references('id')->on('stock_purchase')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')->on('locations')
                ->onDelete('cascade');

            $table->unique('batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_purchase_items');
    }
};
