<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_trip_stocks', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_trip_stocks', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
