<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('transfer_date');
            $table->string('reference_no')->nullable(); // manual/auto
            $table->enum('transfer_type', ['inter'])->default('inter');

            // From / To
            $table->unsignedBigInteger('from_location_id');
            $table->unsignedBigInteger('to_location_id');

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('from_location_id')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('to_location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stock_transfers');
    }
};
