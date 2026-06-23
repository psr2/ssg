<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    

    public function up(): void
    {
        Schema::create('fleet_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('location');

            $table->json('customer_phone')->nullable(); // Multiple phones in JSON
            $table->foreignId('route_id')
                  ->constrained('fleet_routes')
                  ->cascadeOnDelete(); // maintain referential integrity
            $table->timestamps();

            // Indexes for performance
            $table->index('customer_name');
            $table->index('route_id');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_customers');
    }

};
