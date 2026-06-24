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
        try {
            \Illuminate\Support\Facades\DB::table('master_stock_out')
                ->whereNotIn('out_type', ['sale', 'transfer', 'return', 'wastage'])
                ->update(['out_type' => 'sale']);
        } catch (\Exception $e) {
            // Ignore if table or column doesn't exist
        }

        Schema::table('master_stock_out', function (Blueprint $table) {
            $table->enum('out_type', ['sale', 'transfer', 'return', 'wastage'])->default('sale')->change();
        });
    }
};
