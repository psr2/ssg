<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_trip_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fleet_trip_id'); // FK to trips
            $table->unsignedBigInteger('product_id');   // FK to products
            $table->unsignedBigInteger('location_id')->nullable(); // optional, if you track where stock is sent
            $table->string('batch')->nullable();
            $table->string('grade')->nullable();
            $table->decimal('qty_sent', 10, 2)->default(0);
            $table->decimal('qty_returned', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('fleet_trip_id')->references('id')->on('fleet_trips')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_trip_stocks');
    }
};
