<?php

namespace Modules\Inventory\DTO\ProductUnitMeasurement;

class CreateProductUnitDto
{

    public string $unit;
    public string $abbreviation;

    public function __construct(string $unit, string $abbreviation)
    {
        $this->unit = $unit;
        $this->abbreviation = $abbreviation;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['unit'],
            $data['abbreviation']
        );
    }
}
