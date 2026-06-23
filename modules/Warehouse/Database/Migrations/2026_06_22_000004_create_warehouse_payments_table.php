<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment records for warehouse sales.
     * Multiple payments can exist per sale (partial, then settling balance).
     */
    public function up(): void
    {
        Schema::create('warehouse_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->decimal('amount', 12, 2);
            $table->string('method')->default('Cash');
            $table->string('reference_number')->nullable(); // bill number
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')
                ->references('id')
                ->on('warehouse_sales')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_payments');
    }
};
