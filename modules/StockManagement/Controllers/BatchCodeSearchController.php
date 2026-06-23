<?php

namespace Modules\StockManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockManagement\Services\StockMovement\BatchCode\SearchBatchCode;

class BatchCodeSearchController extends Controller
{
    protected SearchBatchCode $search;

    public function __construct(SearchBatchCode $search)
    {
        $this->search = $search;
    }

    public function search(Request $request)
    {
        $results = $this->search->handle($request->all());

        return response()->json($results);
    }
}
