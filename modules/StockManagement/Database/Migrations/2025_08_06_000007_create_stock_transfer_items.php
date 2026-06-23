<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id'); // FK to stock_transfers
            $table->unsignedBigInteger('product_id');
            $table->string('batch_code')->nullable();
            $table->string('grade')->nullable();
            $table->decimal('quantity', 15, 3); // supports precision
            $table->string('unit'); // Kg, Box...
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stock_transfer_items');
    }
};
