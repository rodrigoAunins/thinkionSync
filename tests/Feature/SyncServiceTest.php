<?php

namespace Tests\Feature;

use App\Services\Thinkion\ApiClient;
use App\Services\Thinkion\Sync\SyncService;
use App\Services\Thinkion\Reports\ReportDefinition;
use App\Services\Thinkion\Mappers\VentasReportMapper;
use App\Repositories\Domain\VentaRepository;
use App\Enums\ReportType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncServiceTest extends TestCase
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

    private function createTestReport(): ReportDefinition
    {
        return new ReportDefinition(
            reportId: 233,
            name: 'test_ventas',
            description: 'Test report',
            type: ReportType::TRANSACTION,
            mapperClass: VentasReportMapper::class,
            repositoryClass: VentaRepository::class,
        );
    }

    public function test_sync_inserts_new_records(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [
                    [
                        'nro_venta' => 1001,
                        'fecha_venta' => '2025-01-15',
                        'turno' => 1,
                        'ingresos' => 15000.50,
                        'caja' => 1,
                    ],
                    [
                        'nro_venta' => 1002,
                        'fecha_venta' => '2025-01-15',
                        'turno' => 2,
                        'ingresos' => 20000.00,
                        'caja' => 2,
                    ],
                ],
                'page' => null,
            ], 200),
        ]);

        $syncService = app(SyncService::class);
        $report = $this->createTestReport();

        $result = $syncService->syncReport(
            $report,
            Carbon::parse('2025-01-15'),
            Carbon::parse('2025-01-15'),
            [1]
        );

        $this->assertEquals(2, $result['rows_received']);
        $this->assertEquals(2, $result['rows_inserted']);
        $this->assertEquals(0, $result['rows_updated']);
        $this->assertEquals(0, $result['rows_failed']);

        $this->assertDatabaseHas('ventas', ['nro_venta' => 1001, 'ingresos' => 15000.50]);
        $this->assertDatabaseHas('ventas', ['nro_venta' => 1002, 'ingresos' => 20000.00]);
    }

    public function test_sync_updates_existing_records(): void
    {
        // Use Http::sequence for two consecutive calls
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::sequence()
                // First sync — original data
                ->push([
                    'data' => [
                        ['nro_venta' => 2001, 'fecha_venta' => '2025-02-01', 'ingresos' => 10000.00],
                    ],
                    'page' => null,
                ], 200)
                // Second sync — updated data
                ->push([
                    'data' => [
                        ['nro_venta' => 2001, 'fecha_venta' => '2025-02-01', 'ingresos' => 25000.00],
                    ],
                    'page' => null,
                ], 200),
        ]);

        $syncService = app(SyncService::class);
        $report = $this->createTestReport();

        // First sync
        $syncService->syncReport($report, Carbon::parse('2025-02-01'), Carbon::parse('2025-02-01'), [1]);
        $this->assertDatabaseHas('ventas', ['nro_venta' => 2001, 'ingresos' => 10000.00]);

        // Second sync — same nro_venta + fecha_venta → should update
        $result = $syncService->syncReport($report, Carbon::parse('2025-02-01'), Carbon::parse('2025-02-01'), [1]);

        $this->assertEquals(1, $result['rows_updated']);
        $this->assertDatabaseHas('ventas', ['nro_venta' => 2001, 'ingresos' => 25000.00]);
    }

    public function test_sync_handles_pagination(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::sequence()
                ->push([
                    'data' => [['nro_venta' => 3001, 'fecha_venta' => '2025-03-01', 'ingresos' => 1000]],
                    'page' => 'page2',
                ], 200)
                ->push([
                    'data' => [['nro_venta' => 3002, 'fecha_venta' => '2025-03-01', 'ingresos' => 2000]],
                    'page' => null,
                ], 200),
        ]);

        $syncService = app(SyncService::class);
        $result = $syncService->syncReport(
            $this->createTestReport(),
            Carbon::parse('2025-03-01'),
            Carbon::parse('2025-03-01'),
            [1]
        );

        $this->assertEquals(2, $result['rows_received']);
        $this->assertEquals(2, $result['rows_inserted']);
    }

    public function test_sync_records_run_in_thinkion_sync_runs_table(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [['nro_venta' => 4001, 'fecha_venta' => '2025-04-01']],
                'page' => null,
            ], 200),
        ]);

        $syncService = app(SyncService::class);
        $syncService->syncReport(
            $this->createTestReport(),
            Carbon::parse('2025-04-01'),
            Carbon::parse('2025-04-01'),
            [1]
        );

        $this->assertDatabaseHas('thinkion_sync_runs', [
            'report_id' => 233,
            'report_name' => 'test_ventas',
            'status' => 'completed',
        ]);
    }

    public function test_sync_stores_raw_data(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [['nro_venta' => 5001, 'fecha_venta' => '2025-05-01', 'custom' => 'data']],
                'page' => null,
            ], 200),
        ]);

        $syncService = app(SyncService::class);
        $syncService->syncReport(
            $this->createTestReport(),
            Carbon::parse('2025-05-01'),
            Carbon::parse('2025-05-01'),
            [1]
        );

        $this->assertDatabaseHas('thinkion_raw_reports', [
            'report_id' => 233,
        ]);
    }

    public function test_sync_handles_empty_response(): void
    {
        Http::fake([
            'https://test123.thinkerp.cc/*' => Http::response([
                'data' => [],
                'page' => null,
            ], 200),
        ]);

        $syncService = app(SyncService::class);
        $result = $syncService->syncReport(
            $this->createTestReport(),
            Carbon::parse('2025-06-01'),
            Carbon::parse('2025-06-01'),
            [1]
        );

        $this->assertEquals(0, $result['rows_received']);
        $this->assertEquals(0, $result['rows_inserted']);
    }
}
