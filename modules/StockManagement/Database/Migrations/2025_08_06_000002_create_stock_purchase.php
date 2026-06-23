<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_purchase', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_stock_in_id'); // FK to master_stock_in
            $table->string('vendor')->nullable();
            $table->string('invoice_number');
            $table->date('purchase_date');
            $table->string('batch_code')->nullable();
            $table->timestamps();
            
            $table->foreign('master_stock_in_id')
                  ->references('id')->on('master_stock_in')
                  ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('stock_purchase');
    }
};
