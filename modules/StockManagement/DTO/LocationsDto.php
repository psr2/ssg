<?php
namespace Modules\StockManagement\DTO;

class LocationsDto{

    public readonly string $location_name;
    public readonly string $location_type;
    public readonly string $location_address;



    public function __construct( string $location_name , string $location_type ,string $location_address){

        $this->location_name=$location_name;
        $this->location_type=$location_type;
        $this->location_address=$location_address;

    }

    public static function fromArray($data):self{
         
        return new self(
            $data['location_name'],
            $data['location_type'],
            $data['location_address']


         );

    }

}