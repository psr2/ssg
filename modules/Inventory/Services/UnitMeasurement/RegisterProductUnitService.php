<?php

namespace Modules\Inventory\Services\UnitMeasurement;

use Modules\Inventory\DTO\ProductUnitMeasurement\CreateProductUnitDto as ProductUnitDto;
use Modules\Inventory\Repositories\UnitMeasurement\ProductMeasurementUnitRepository;

class RegisterProductUnitService
{
    protected ProductMeasurementUnitRepository $repository;

    public function __construct(ProductMeasurementUnitRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerUnit(ProductUnitDto $dto)
    {
        return $this->repository->createMeasurementUnit($dto);
    }
}
