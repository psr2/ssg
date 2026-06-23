<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_stock_out', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id'); // where stock is going out from
            $table->string('reference_no')->nullable(); // bill no / fleet ref / manual ref
            $table->enum('out_type', ['sale', 'transfer', 'return', 'wastage'])->default('sale');
            $table->date('out_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_stock_out');
    }
};
