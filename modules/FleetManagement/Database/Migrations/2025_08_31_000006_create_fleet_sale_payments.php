<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_sale_id')
                ->constrained('fleet_sales')
                ->onDelete('cascade');

            $table->decimal('amount', 12, 2);
            $table->date('payment_date')->nullable();
            $table->string('payment_mode')->nullable(); // cash, UPI, credit note, etc.
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_sale_payments');
    }
};
