<?php

namespace Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Warehouse\Models\WarehouseSale;

class WarehouseCreditController extends Controller
{
    /**
     * Render the credit details page.
     */
    public function index()
    {
        // By default, list all active credits (due_amount > 0)
        $credits = WarehouseSale::with('customer')
            ->where('due_amount', '>', 0)
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        return view('warehouse::warehouse_credits', compact('credits'));
    }

    /**
     * Fetch credit records filtered by date range via AJAX.
     */
    public function search(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $credits = WarehouseSale::with('customer')
            ->where('due_amount', '>', 0)
            ->whereBetween('sale_date', [$request->start_date, $request->end_date])
            ->orderBy('sale_date', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'id'            => $sale->id,
                    'bill_no'       => $sale->id, // Use sale id or bill_no if stored in database
                    'customer_name' => $sale->customer->name ?? 'Unknown',
                    'sale_date'     => $sale->sale_date,
                    'total_amount'  => $sale->total_amount,
                    'paid_amount'   => $sale->paid_amount,
                    'due_amount'    => $sale->due_amount,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $credits,
        ]);
    }
}
