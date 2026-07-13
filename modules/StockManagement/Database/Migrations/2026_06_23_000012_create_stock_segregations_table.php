<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {}
    
    public function down(): void
    {
        Schema::dropIfExists('stock_segregation_items');
        Schema::dropIfExists('stock_segregations');
    }
};
