<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_trips', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_trips', 'status')) {
                $table->string('status')->default('active')->after('tag'); // active, cancelled
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleet_trips', function (Blueprint $table) {
            if (Schema::hasColumn('fleet_trips', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
