<?php

namespace Modules\Expenses\Controllers;

use App\Http\Controllers\Controller;
use Modules\Expenses\Contracts\CategoryInterface;
use Illuminate\Support\Facades\Log;
use Modules\Expenses\Models\Expense;

class ExpenseUIController extends Controller
{
    protected CategoryInterface $interface;

    public function __construct(CategoryInterface $interface)
    {
        $this->interface = $interface;
    }

    /**
     * Show the expense page with categories.
     */
    public function index()
    {
        try {
            $response = $this->interface->getExpenseCategories();

            // Check if response is structured like an API response
            if (is_array($response) && isset($response['success']) && $response['success'] === false) {
                Log::warning('Expense categories not found: ' . ($response['message'] ?? 'No message'));
                $categories = []; // safe empty array for the view
            } else {
                // If response contains data, use it; otherwise empty
                $categories = $response['data'] ?? $response ?? [];
            }

        } catch (\Throwable $e) {
            // Log any unexpected errors
            Log::error('Error fetching expense categories: ' . $e->getMessage());
            $categories = [];
        }

        $list=Expense::all();

        // Pass categories to the Blade view
        return view('expense::expense', ['data' => $categories ,'list'=>$list]);
    }
}
