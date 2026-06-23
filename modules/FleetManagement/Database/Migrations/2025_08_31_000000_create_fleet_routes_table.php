<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fleet_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "City Center"
            $table->string('description')->nullable(); // optional details
            $table->timestamps();
            $table->softDeletes(); // in case you want to archive/delete without losing history
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_routes');
    }
};
