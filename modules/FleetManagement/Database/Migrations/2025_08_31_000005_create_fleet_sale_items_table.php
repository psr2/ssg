<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_sale_id')
                ->constrained('fleet_sales')
                ->onDelete('cascade');

            $table->string('product_name');
            $table->decimal('quantity', 12, 3);
            $table->string('unit')->nullable(); // kg, piece, etc.
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_sale_items');
    }
};
