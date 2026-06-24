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
        Schema::create('stock_segregations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('product_id');
            $table->string('parent_batch_code');
            $table->decimal('parent_quantity', 12, 2);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('segregation_date');
            $table->timestamps();

            // Foreign keys
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('parent_batch_code')->references('batch')->on('stock_purchase_items')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('stock_segregation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_segregation_id');
            $table->string('grade');
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->decimal('unit_cost', 12, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('stock_segregation_id')->references('id')->on('stock_segregations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_segregation_items');
        Schema::dropIfExists('stock_segregations');
    }
};
