<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warehouse customers - customers who purchase directly from the warehouse.
     * Reuses the locations table for warehouse_id (same as shop_customers pattern).
     */
    public function up(): void
    {
        Schema::create('warehouse_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id'); // which warehouse this customer belongs to
            $table->string('name');
            $table->string('phone')->nullable()->unique();
            $table->string('address')->nullable();
            $table->string('location')->nullable();
            $table->decimal('credit_balance', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_customers');
    }
};
