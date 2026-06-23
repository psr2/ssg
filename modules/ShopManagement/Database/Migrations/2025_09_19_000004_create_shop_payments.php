<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('shop_sales')->cascadeOnDelete();
            $table->decimal('amount', 12, 2); // partial or full
            $table->enum('method', ['cash', 'card', 'upi', 'credit'])->default('cash');
            $table->string('reference_number')->nullable(); // UPI txn id, cheque no, etc.
            $table->dateTime('paid_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_payments');
    }
};
