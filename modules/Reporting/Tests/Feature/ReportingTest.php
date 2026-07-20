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

    public function test_reports_dashboard_renders_successfully(): void
    {
        $response = $this->get('/reports');
        $response->assertStatus(200);
        $response->assertSee('Standard Reports Center');
    }

    public function test_all_standard_report_types_render_successfully(): void
    {
        $types = ['stock', 'ledger', 'warehouse', 'shop', 'fleet', 'expenses', 'adjustments', 'credits'];

        foreach ($types as $type) {
            $response = $this->get('/reports/' . $type);
            $response->assertStatus(200);
        }
    }

    public function test_pdf_download_generates_valid_pdf_stream(): void
    {
        $response = $this->get('/reports/download/pdf/stock');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
    }

    public function test_csv_download_generates_valid_csv_file(): void
    {
        $response = $this->get('/reports/download/csv/warehouse');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertSee('Warehouse Sales Performance Report');
    }

    public function test_pdf_printable_preview_renders_successfully(): void
    {
        $response = $this->get('/reports/preview/expenses');
        $response->assertStatus(200);
        $response->assertSee('Expense Summary');
    }
}

