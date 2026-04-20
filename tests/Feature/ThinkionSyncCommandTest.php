<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThinkionSyncCommandTest extends TestCase
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
            'thinkion.sync.default_report_ids' => [233],
            'thinkion.sync.default_establishments' => [1],
            'thinkion.sync.days_back' => 7,
            'thinkion.logging.requests' => false,
            'thinkion.logging.responses' => false,
        ]);
    }

    public function test_sync_command_runs_successfully(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['nro_venta' => 1, 'fecha_venta' => '2025-01-01', 'ingresos' => 100],
                ],
                'page' => null,
            ], 200),
        ]);

        $this->artisan('thinkion:sync', [
            '--report' => 233,
            '--start' => '2025-01-01',
            '--end' => '2025-01-01',
            '--establishments' => '1',
        ])->assertSuccessful();

        $this->assertDatabaseHas('ventas', ['nro_venta' => 1]);
    }

    public function test_sync_daily_command_runs_successfully(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['nro_venta' => 2, 'fecha_venta' => now()->format('Y-m-d'), 'ingresos' => 200],
                ],
                'page' => null,
            ], 200),
        ]);

        $this->artisan('thinkion:sync-daily')
            ->assertSuccessful();
    }

    public function test_sync_command_with_invalid_resource(): void
    {
        $this->artisan('thinkion:sync', ['--resource' => 'invalid_type'])
            ->assertFailed();
    }

    public function test_test_connection_command_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [['id' => 1, 'name' => 'Test']],
                'page' => null,
            ], 200),
        ]);

        $this->artisan('thinkion:test-connection')
            ->assertSuccessful();
    }

    public function test_test_connection_command_failure(): void
    {
        Http::fake([
            '*' => Http::response('Unauthorized', 401),
        ]);

        $this->artisan('thinkion:test-connection')
            ->assertFailed();
    }
}
