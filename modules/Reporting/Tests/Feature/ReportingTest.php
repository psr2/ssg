<?php

namespace Modules\Reporting\Tests\Feature;

use Tests\TestCase;

class ReportingTest extends TestCase
{
    public function test_reports_overview_returns_successful_json_response(): void
    {
        $response = $this->getJson('/reports/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'warehouse_stock',
                    'shop_stock'
                ],
                'generated_at'
            ]);
    }
}
