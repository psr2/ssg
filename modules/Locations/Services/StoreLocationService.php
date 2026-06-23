<?php

namespace Modules\Locations\Services;

use Illuminate\Support\Facades\Log;
use Modules\Locations\DTO\LocationsDto;
use Modules\Locations\Repositories\LocationsRepository;


class StoreLocationService

{


    public $repository;

    public function __construct(LocationsRepository $locationsRepository) {

        $this->repository=$locationsRepository;
        
    } 

    public function create($data):void
    {
        
       $this->repository->create([

            'location_name' => $data->location_name,
            'location_type' => $data->location_type,
            'location_address' => $data->location_address,
            'location_abbreviation' => $data->location_abbreviation,



        ]);
    }

    public function update($data){

    }

   
}
