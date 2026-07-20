<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Workflow\Concerns\CreatesWorkflowFixtures;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use App\Models\User;

abstract class BaseWorkflowTestCase extends TestCase
{
    use RefreshDatabase, CreatesWorkflowFixtures;

    protected $tomato;
    protected $onion;
    protected $gradeBo;
    protected $unitKg;
    protected $theniWarehouse;
    protected $theniShop;
    protected $maduraiWarehouse;
    protected $maduraiShop;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->unitKg = UnitOfMeasurement::firstOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'Kilogram']
        );

        $this->tomato = Products::firstOrCreate(
            ['sku' => 'veg_tom'],
            [
                'name' => 'Tomato',
                'abbreviation' => 'tom',
                'unit_id' => $this->unitKg->id
            ]
        );

        $this->onion = Products::firstOrCreate(
            ['sku' => 'veg_on'],
            [
                'name' => 'Onion',
                'abbreviation' => 'on',
                'unit_id' => $this->unitKg->id
            ]
        );

        $this->gradeBo = ProductGrade::firstOrCreate(
            ['code' => 'BO'],
            [
                'name' => 'Big',
                'is_active' => true
            ]
        );

        $this->theniWarehouse = LocationModel::create([
            'name' => 'Theni Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'TW',
            'status' => 'active'
        ]);

        $this->theniShop = LocationModel::create([
            'name' => 'Theni Shop',
            'type' => 'shop',
            'abbreviation' => 'TS',
            'status' => 'active'
        ]);

        $this->maduraiWarehouse = LocationModel::create([
            'name' => 'Madurai Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'MW',
            'status' => 'active'
        ]);

        $this->maduraiShop = LocationModel::create([
            'name' => 'Madurai Shop',
            'type' => 'shop',
            'abbreviation' => 'MS',
            'status' => 'active'
        ]);
    }
}
