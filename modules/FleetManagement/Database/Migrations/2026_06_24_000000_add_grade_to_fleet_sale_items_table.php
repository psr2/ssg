<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('fleet_sale_items')) {
            Schema::table('fleet_sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('fleet_sale_items', 'grade')) {
                    $table->string('grade')->nullable()->after('unit');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_sale_items')) {
            Schema::table('fleet_sale_items', function (Blueprint $table) {
                if (Schema::hasColumn('fleet_sale_items', 'grade')) {
                    $table->dropColumn('grade');
                }
            });
        }
    }
};
