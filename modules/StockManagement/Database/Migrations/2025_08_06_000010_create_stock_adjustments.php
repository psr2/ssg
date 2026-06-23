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

            // Original stock line item
            $table->unsignedBigInteger('stock_purchase_item_id');

            // Quantity before & after
            $table->decimal('old_quantity', 10, 2);
            $table->decimal('new_quantity', 10, 2);

            // Units before & after
            $table->unsignedBigInteger('old_unit_id')->nullable();
            $table->unsignedBigInteger('new_unit_id')->nullable();

            // Location before & after
            $table->unsignedBigInteger('old_location_id')->nullable();
            $table->unsignedBigInteger('new_location_id')->nullable();

            // Reason/type
            $table->enum('adjustment_type', [
                'CORRECTION',
                'LOCATION_CHANGE',
                'QUANTITY_CHANGE',
                'UNIT_CHANGE',
                'DAMAGED',
                'LOST',
                'OTHER'
            ])->default('CORRECTION');

            $table->text('remarks')->nullable();

            // Who performed it
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('stock_purchase_item_id')
                ->references('id')->on('stock_purchase_items')
                ->onDelete('cascade');

            $table->foreign('old_location_id')
                ->references('id')->on('locations')
                ->nullOnDelete();

            $table->foreign('new_location_id')
                ->references('id')->on('locations')
                ->nullOnDelete();

            $table->foreign('old_unit_id')
                ->references('id')->on('units')
                ->nullOnDelete();

            $table->foreign('new_unit_id')
                ->references('id')->on('units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
