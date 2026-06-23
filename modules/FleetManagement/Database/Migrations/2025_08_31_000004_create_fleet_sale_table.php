<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_trip_id')
                ->constrained('fleet_trips')
                ->onDelete('cascade'); // sale tied to trip

            $table->string('bill_number')->nullable(); // manually entered
            $table->string('customer_name')->nullable(); // manual entry
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_sales');
    }
};
