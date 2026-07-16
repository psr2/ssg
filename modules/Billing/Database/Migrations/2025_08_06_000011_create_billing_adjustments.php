<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('sale_type'); // warehouse, shop, fleet
            $table->unsignedBigInteger('sale_id');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('adjusted_amount', 15, 2); // negative or positive delta
            $table->decimal('new_amount', 15, 2);
            $table->string('reason'); // price_correction, discretionary_discount, billing_error, tax_adjustment, etc.
            $table->unsignedBigInteger('adjusted_by');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_adjustments');
    }
};
