<?php

namespace Tests\Feature;

use App\Services\Thinkion\Sync\SyncService;
use App\Services\Thinkion\Reports\ReportDefinition;
use App\Services\Thinkion\Mappers\VentasReportMapper;
use App\Repositories\Domain\VentaRepository;
use App\Enums\ReportType;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'thinkion.api.client_code' => 'test123',
            'thinkion.api.token' => 'test-token',
            'thinkion.api.timeout' => 10,
            'thinkion.api.retries' => 1,
            'thinkion.api.retry_sleep_ms' => 10,
            'thinkion.api.max_days_per_request' => 30,
            'thinkion.logging.requests' => false,
            'thinkion.logging.responses' => false,
        ]);
    }

    /**
     * CRITICAL: Running sync twice with the same data must NOT create duplicates.
     */
    public function test_consecutive_syncs_do_not_duplicate_records(): void
    {
        $apiResponse = [
            'data' => [
                ['nro_venta' => 9001, 'fecha_venta' => '2025-01-01', 'ingresos' => 5000],
                ['nro_venta' => 9002, 'fecha_venta' => '2025-01-01', 'ingresos' => 3000],
                ['nro_venta' => 9003, 'fecha_venta' => '2025-01-01', 'ingresos' => 7000],
            ],
            'page' => null,
        ];

        // Ensure 3 exact identical responses
        Http::fake([
            '*' => Http::sequence()
                ->push($apiResponse, 200)
                ->push($apiResponse, 200)
                ->push($apiResponse, 200),
        ]);

        $report = new ReportDefinition(
            reportId: 233,
            name: 'idempotency_test',
            description: 'Test',
            type: ReportType::TRANSACTION,
            mapperClass: VentasReportMapper::class,
            repositoryClass: VentaRepository::class,
        );

        $syncService = app(SyncService::class);

        // First sync
        $result1 = $syncService->syncReport($report, Carbon::parse('2025-01-01'), Carbon::parse('2025-01-01'), [1]);

        $this->assertEquals(3, $result1['rows_inserted']);
        $this->assertEquals(3, Venta::count());

        // Second sync (same data)
        $result2 = $syncService->syncReport($report, Carbon::parse('2025-01-01'), Carbon::parse('2025-01-01'), [1]);

        $this->assertEquals(3, $result2['rows_updated']);
        $this->assertEquals(0, $result2['rows_inserted']);

        // CRITICAL: Record count must remain the same
        $this->assertEquals(3, Venta::count(), "Duplicates detected after consecutive sync!");

        // Third sync
        $syncService->syncReport($report, Carbon::parse('2025-01-01'), Carbon::parse('2025-01-01'), [1]);

        $this->assertEquals(3, Venta::count(), "Duplicates detected after third consecutive sync!");
    }

    public function test_updated_data_reflects_in_database(): void
    {
        $report = new ReportDefinition(
            reportId: 233,
            name: 'update_test',
            description: 'Test',
            type: ReportType::TRANSACTION,
            mapperClass: VentasReportMapper::class,
            repositoryClass: VentaRepository::class,
        );

        Http::fake([
            '*' => Http::sequence()
                // First sync - original data
                ->push([
                    'data' => [
                        ['nro_venta' => 8001, 'fecha_venta' => '2025-02-01', 'ingresos' => 1000],
                    ],
                    'page' => null,
                ], 200)
                // Second sync - updated data
                ->push([
                    'data' => [
                        ['nro_venta' => 8001, 'fecha_venta' => '2025-02-01', 'ingresos' => 9999],
                    ],
                    'page' => null,
                ], 200),
        ]);

        $syncService = app(SyncService::class);

        // First sync
        $syncService->syncReport($report, Carbon::parse('2025-02-01'), Carbon::parse('2025-02-01'), [1]);
        $this->assertDatabaseHas('ventas', ['nro_venta' => 8001, 'ingresos' => 1000]);

        // Second sync
        $syncService->syncReport($report, Carbon::parse('2025-02-01'), Carbon::parse('2025-02-01'), [1]);

        $this->assertDatabaseHas('ventas', ['nro_venta' => 8001, 'ingresos' => 9999]);
        $this->assertDatabaseMissing('ventas', ['nro_venta' => 8001, 'ingresos' => 1000]);
        $this->assertEquals(1, Venta::where('nro_venta', 8001)->count());
    }
}
