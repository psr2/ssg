<?php

namespace Modules\Locations\API\Internal\Services;

use Illuminate\Support\Facades\Log;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\Locations\API\Internal\Repository\LocationRepository;


/**
 * Service class responsible for sharing location data across modules.
 * 
 * This service implements the LocationsInterface and uses
 * LocationRepository to fetch location data.
 * 
 * Todo :: Test pending
 */
class ShareLocationService implements LocationsInterface
{
    /**
     * The repository instance for fetching locations.
     */
    private readonly LocationRepository $repo;

    /**
     * Inject the LocationRepository dependency.
     *
     * @param LocationRepository $repo
     */
    public function __construct(LocationRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Return all locations as a JSON-encoded string.
     *
     * @return string JSON representation of locations
     */
    public function shareLocation(): string
    {
        return json_encode($this->repo->get());
    }
}
