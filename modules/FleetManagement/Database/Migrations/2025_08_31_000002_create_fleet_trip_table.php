<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_trips', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('vehicle_id');
            //generate Tag dynamic like batch code if needed 
            $table->string('tag');

            // $table->unsignedBigInteger('driver_id')->nullable();

            // Trip details
            $table->date('start_date');
            // $table->enum('status', ['pending', 'ongoing', 'completed', 'cancelled'])->default('pending');

            $table->timestamps();

            // Constraints
            $table->foreign('route_id')->references('id')->on('fleet_routes')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('fleet_vehicles')->onDelete('cascade');
            
            //If needed add in future
            // $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_trips');
    }
};
