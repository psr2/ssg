<?php

namespace Modules\ShopManagement\Services;

use Modules\ShopManagement\Repository\UpdatePaymentRepository;
use Modules\ShopManagement\Requests\PaymentUpdateRequest;

Class UpdatePayment{

    public function __construct(private UpdatePaymentRepository $update){}

    public function handle(PaymentUpdateRequest $request){
        
            $this->update->process($request->validated());

    }

}