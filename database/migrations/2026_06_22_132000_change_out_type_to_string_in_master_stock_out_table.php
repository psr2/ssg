<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_stock_out', function (Blueprint $table) {
            $table->string('out_type')->default('sale')->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_stock_out', function (Blueprint $table) {
            $table->enum('out_type', ['sale', 'transfer', 'return', 'wastage'])->default('sale')->change();
        });
    }
};
