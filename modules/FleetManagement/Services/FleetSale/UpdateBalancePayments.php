<?php

namespace Modules\FleetManagement\Services\FleetSale;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Models\FleetSalePayment;
use Exception;

class UpdateBalancePayments
{
    public function update(array $request)
    {

        Log::debug($request);

       return $balance = FleetSalePayment::create([
        
            'fleet_sale_id' => $request['id'],
            'amount' => $request['paymentAmount'],
            'payment_mode' => $request['payment-method'],
            'payment_date' => $request['payment-date'],
            'notes'=>"Nil"

        ]);
    }
}
