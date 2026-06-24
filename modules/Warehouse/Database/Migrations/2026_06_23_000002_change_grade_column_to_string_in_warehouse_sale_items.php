<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_sale_items', function (Blueprint $table) {
            $table->string('grade', 50)->change();
        });
    }

    public function down(): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('warehouse_sale_items')->update(['grade' => 1]);
        } catch (\Exception $e) {
            // Ignore if table or records don't exist
        }

        Schema::table('warehouse_sale_items', function (Blueprint $table) {
            $table->integer('grade')->change();
        });
    }
};
