<?php

namespace Modules\Inventory\Repositories\UnitMeasurement;

use Modules\Inventory\DTO\ProductUnitMeasurement\CreateProductunitDto as UnitMeasurementDto;
use  Modules\Inventory\Models\UnitOfMeasurement as UnitModel;


class ProductMeasurementUnitRepository
{

   public function createMeasurementUnit(UnitMeasurementDto $data): UnitModel
   {

      return UnitModel::create([
         'name' => $data->unit,
         'abbreviation' => $data->abbreviation
      ]);
   }

   
}
