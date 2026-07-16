<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('product_id');
            $table->string('batch_code');
            $table->string('grade')->nullable();
            $table->decimal('original_qty', 15, 2);
            $table->decimal('adjusted_qty', 15, 2); // The adjustment delta (negative or positive)
            $table->decimal('new_qty', 15, 2);      // Resulting quantity
            $table->string('reason');              // audit_difference, damage, theft, etc.
            $table->string('status')->default('approved'); // approved, pending_approval
            $table->unsignedBigInteger('adjusted_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
