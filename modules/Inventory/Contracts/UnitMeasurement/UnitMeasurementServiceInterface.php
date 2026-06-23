<?php

namespace Modules\Inventory\Contracts\UnitMeasurement;

use Modules\Inventory\DTO\UnitMeasurement\UnitDto;
use Modules\Inventory\Models\UnitOfMeasurement as Unit;

interface UnitMeasurementServiceInterface{

  public function registerUnit(UnitDto $data):Unit;


}