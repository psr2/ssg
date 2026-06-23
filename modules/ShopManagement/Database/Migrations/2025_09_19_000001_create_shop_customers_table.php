<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id'); // Add the shop_id column
            $table->string('name');
            $table->string('phone')->nullable()->unique();
            $table->string('address')->nullable();
            $table->decimal('credit_balance', 12, 2)->default(0); // running balance
            // $table->decimal('outstanding_balance', 12, 2)->default(0); // running balance (if you want fast lookup)

            $table->timestamps();

            // Add the foreign key constraint
            $table->foreign('shop_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_customers');
    }
};
