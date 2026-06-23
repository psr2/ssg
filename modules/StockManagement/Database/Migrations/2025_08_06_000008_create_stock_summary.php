<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_summary', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
            
            $table->string('batch_id');
            $table->string('grade');



            $table->decimal('current_qty', 12, 2)->default(0);
            $table->decimal('reserved_qty', 12, 2)->default(0);
            $table->string('unit')->default(NULL);


            $table->timestamps();

            $table->unique(['product_id', 'location_id', 'batch_id'], 'uq_stock_summary');

            // $table->index('product_id');
            // $table->index('location_id');
            // $table->index('batch_id');

            // Foreign keys (optional, uncomment if available)
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            // $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_summary');
    }
};
