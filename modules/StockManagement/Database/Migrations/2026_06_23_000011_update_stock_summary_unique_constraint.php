<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_summary', function (Blueprint $table) {
            // Create separate indexes first so MySQL foreign keys are satisfied
            $table->index('product_id', 'idx_product_id');
            $table->index('location_id', 'idx_location_id');
        });

        Schema::table('stock_summary', function (Blueprint $table) {
            // Drop old unique constraint and add new one
            $table->dropUnique('uq_stock_summary');
            $table->unique(['product_id', 'location_id', 'batch_id', 'grade'], 'uq_stock_summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Resolve duplicates by merging rows with same product_id, location_id, batch_id
        $duplicates = DB::table('stock_summary')
            ->select('product_id', 'location_id', 'batch_id')
            ->groupBy('product_id', 'location_id', 'batch_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            // Find all rows for this combination
            $rows = DB::table('stock_summary')
                ->where('product_id', $dup->product_id)
                ->where('location_id', $dup->location_id)
                ->where('batch_id', $dup->batch_id)
                ->get();

            // Sum quantities
            $totalCurrent = $rows->sum('current_qty');
            $totalReserved = $rows->sum('reserved_qty');

            // Keep the first one and update it
            $firstId = $rows->first()->id;
            DB::table('stock_summary')
                ->where('id', $firstId)
                ->update([
                    'current_qty' => $totalCurrent,
                    'reserved_qty' => $totalReserved,
                ]);

            // Delete the others
            DB::table('stock_summary')
                ->where('product_id', $dup->product_id)
                ->where('location_id', $dup->location_id)
                ->where('batch_id', $dup->batch_id)
                ->where('id', '!=', $firstId)
                ->delete();
        }

        // 1. Drop foreign keys safely to unlock index modifications
        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        } catch (\Exception $e) {
            // Ignore if already dropped
        }

        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
            });
        } catch (\Exception $e) {
            // Ignore if already dropped
        }

        // 2. Drop the temporary indexes and old unique constraint safely
        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->dropIndex('idx_product_id');
            });
        } catch (\Exception $e) {
            // Ignore if it doesn't exist
        }

        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->dropIndex('idx_location_id');
            });
        } catch (\Exception $e) {
            // Ignore if it doesn't exist
        }

        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->dropUnique('uq_stock_summary');
            });
        } catch (\Exception $e) {
            // Ignore if it doesn't exist
        }

        // 3. Recreate the old unique constraint safely
        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->unique(['product_id', 'location_id', 'batch_id'], 'uq_stock_summary');
            });
        } catch (\Exception $e) {
            // Ignore if already exists
        }

        // 4. Recreate the foreign keys safely
        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        } catch (\Exception $e) {
            // Ignore if already exists
        }

        try {
            Schema::table('stock_summary', function (Blueprint $table) {
                $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
            });
        } catch (\Exception $e) {
            // Ignore if already exists
        }
    }
};
