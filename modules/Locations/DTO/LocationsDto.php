<?php

namespace Modules\Locations\DTO;

class LocationsDto
{

    public readonly string $location_name;
    public readonly string $location_type;
    public readonly string $location_address;
    public readonly string $location_abbreviation;


    public function __construct(
        string $location_name,
        string $location_type,
        string $location_address,
        string $location_abbreviation
    ) {

        $this->location_name = $location_name;
        $this->location_type = $location_type;
        $this->location_address = $location_address;
        $this->location_abbreviation = $location_abbreviation;

    }

    public static function fromArray($data): self
    {

        return new self(
            $data['location_name'],
            $data['location_type'],
            $data['location_address'],
            $data['location_abbreviation'],



        );
    }
}
