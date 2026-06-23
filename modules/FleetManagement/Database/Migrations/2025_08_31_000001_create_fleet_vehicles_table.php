<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique(); // Vehicle number
            $table->string('model')->nullable();
            $table->string('type')->nullable(); // Bus, Van, Truck
            $table->integer('capacity')->nullable(); // Number of passengers or load
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fleet_vehicles');
    }
};
