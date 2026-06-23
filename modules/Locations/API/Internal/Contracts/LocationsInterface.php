<?php

namespace Modules\Locations\API\Internal\Contracts;

use Modules\Locations\API\Internal\Repository\LocationRepository;

interface LocationsInterface
{
    public function shareLocation():string;
}
