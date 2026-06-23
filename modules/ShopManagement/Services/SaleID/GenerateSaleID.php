<?php

namespace Modules\ShopManagement\Services\SaleID;

class GenerateSaleID{
    
    public static function referenceID(){
        mt_rand(0,1000000);
    }

}