<?php

namespace Modules\FleetManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\FleetManagement\Models\FleetVehicle;

class FleetVehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            [
                'registration_number' => 'KL-06-A-1234',
                'model' => 'Tata 407',
                'type' => 'Truck',
                'capacity' => 3000,
                'notes' => 'Primary delivery truck',
            ],
            [
                'registration_number' => 'KL-06-B-5678',
                'model' => 'Mahindra Bolero Pickup',
                'type' => 'Pickup',
                'capacity' => 1500,
                'notes' => 'Local delivery vehicle',
            ],
            [
                'registration_number' => 'KL-06-C-9012',
                'model' => 'Ashok Leyland Dost',
                'type' => 'Van',
                'capacity' => 1200,
                'notes' => 'Backup transport vehicle',
            ],
            [
                'registration_number' => 'KL-06-D-3456',
                'model' => 'Eicher Pro 2049',
                'type' => 'Truck',
                'capacity' => 3500,
                'notes' => 'Heavy duty transport vehicle',
            ],
        ];

        foreach ($vehicles as $vehicle) {
            FleetVehicle::firstOrCreate(
                ['registration_number' => $vehicle['registration_number']],
                $vehicle
            );
        }
    }
}
