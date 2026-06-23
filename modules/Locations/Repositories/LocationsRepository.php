<?php
namespace Modules\Locations\Repositories;

use Illuminate\Support\Facades\Log;
use Modules\Locations\Models\LocationModel as Location;



class LocationsRepository{

    public  function    create($data):void{

           Location::create([

            'name' => $data['location_name'],
            'type' => $data['location_type'],
            'address'=>$data['location_address'],
            'abbreviation'=>$data['location_abbreviation'],
           

        ]);

    }

    public function update(){

    }

}