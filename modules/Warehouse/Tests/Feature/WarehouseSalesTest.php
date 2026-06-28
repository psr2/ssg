<?php

namespace Modules\Warehouse\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Warehouse\Controllers\WarehouseSaleController;
use Modules\Warehouse\Services\StoreSale;
use Tests\TestCase;
use Modules\Warehouse\Services\UpdatePayment;
use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\Warehouse\Repositories\WarehouseSaleRepository;


class WarehouseOverviewTest extends TestCase{

  use RefreshDatabase;

  public function test_can_create_a_new_sale_with_single_product_item(){
   
    $payload=[];

     $sale=$this->createMock(Storesale::class);
     $products=$this->createMock(ProductInterface::class);
     $update=$this->createMock(UpdatePayment::class);
     $repo=$this->createMock(WarehouseSaleRepository::class);

    $warehouse=new WarehouseSaleController(
         $sale,
         $products,
         $update,
         $repo
    );
    
    $warehouse->store($payload);

  }

  public function test_can_create_new_sale_with_multiple_product_items(){

  }


}