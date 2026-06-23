<?php

namespace Modules\Warehouse\Services;

use Modules\Warehouse\Repositories\UpdateWarehousePaymentRepository;
use Modules\Warehouse\Requests\WarehousePaymentUpdateRequest;

class UpdatePayment
{
    public function __construct(private UpdateWarehousePaymentRepository $repo) {}

    public function handle(WarehousePaymentUpdateRequest $request): void
    {
        $this->repo->process($request->validated());
    }
}
