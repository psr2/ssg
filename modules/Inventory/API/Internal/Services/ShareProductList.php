<?php

namespace Modules\Inventory\API\Internal\Services;

use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\Inventory\Models\Products;

class ShareProductList implements ProductInterface{

    public function shareProductList(){

        return Products::select('id','name')->get();

    }
}