<?php

namespace Modules\ShopManagement\Controllers\Sale;

use App\Http\Controllers\Controller;

Class ShopOverview extends Controller{

    public function index(){
       
        return view("shop_management::shop_overview");
    }
}