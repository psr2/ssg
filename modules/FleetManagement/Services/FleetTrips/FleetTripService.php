<?php

namespace Modules\FleetManagement\Services\FleetTrips;

use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Repository\FleetTripRepository;

class FleetTripService
{
    protected $repo;

    public function __construct(FleetTripRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createTrip(array $data)
    {
        return $this->repo->create($data);
    }

    public function allTrips()
    {

       return $this->repo->allTrips();
    }
}
