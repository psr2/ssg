<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_stock_in', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique(); // Public identifier
            $table->enum('stock_movement_type', ['in','out']);
            $table->enum('stock_in_type', ['purchase', 'return_customer', 'return_fleet']);
            $table->date('stock_in_date');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('master_stock_in');
    }
};
