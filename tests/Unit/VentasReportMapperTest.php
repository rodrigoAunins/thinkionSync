<?php

namespace Tests\Unit;

use App\Services\Thinkion\Mappers\VentasReportMapper;
use Tests\TestCase;

class VentasReportMapperTest extends TestCase
{
    private VentasReportMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new VentasReportMapper();
    }

    public function test_maps_standard_fields_correctly(): void
    {
        $row = [
            'nro_venta' => 1001,
            'fecha_venta' => '2025-01-15',
            'turno' => 1,
            'ingresos' => 15000.50,
            'egresos' => 500.00,
            'fp1' => 8000.00,
            'fp2' => 7000.50,
            'caja' => 1,
        ];

        $mapped = $this->mapper->map($row);

        $this->assertNotNull($mapped);
        $this->assertEquals(1001, $mapped['nro_venta']);
        $this->assertEquals('2025-01-15', $mapped['fecha_venta']);
        $this->assertEquals(1, $mapped['turno']);
        $this->assertEquals(15000.50, $mapped['ingresos']);
        $this->assertEquals(500.00, $mapped['egresos']);
        $this->assertEquals(8000.00, $mapped['fp1']);
        $this->assertEquals(1, $mapped['caja']);
    }

    public function test_maps_english_field_names(): void
    {
        $row = [
            'transaction_number' => 2001,
            'date' => '2025-02-20',
            'shift' => 2,
            'income' => 20000.00,
            'expenses' => 1000.00,
        ];

        $mapped = $this->mapper->map($row);

        $this->assertNotNull($mapped);
        $this->assertEquals(2001, $mapped['nro_venta']);
        $this->assertEquals('2025-02-20', $mapped['fecha_venta']);
        $this->assertEquals(2, $mapped['turno']);
        $this->assertEquals(20000.00, $mapped['ingresos']);
        $this->assertEquals(1000.00, $mapped['egresos']);
    }

    public function test_case_insensitive_matching(): void
    {
        $row = [
            'NRO_VENTA' => 3001,
            'FECHA_VENTA' => '2025-03-10',
            'INGRESOS' => 5000.00,
        ];

        $mapped = $this->mapper->map($row);

        $this->assertNotNull($mapped);
        $this->assertEquals(3001, $mapped['nro_venta']);
        $this->assertEquals('2025-03-10', $mapped['fecha_venta']);
    }

    public function test_skips_empty_rows(): void
    {
        $this->assertNull($this->mapper->map([]));
    }

    public function test_skips_rows_without_nro_venta(): void
    {
        $row = [
            'some_field' => 'value',
            'another_field' => 123,
        ];

        $this->assertNull($this->mapper->map($row));
    }

    public function test_falls_back_to_id_for_nro_venta(): void
    {
        $row = [
            'id' => 5001,
            'fecha_venta' => '2025-04-01',
        ];

        $mapped = $this->mapper->map($row);

        $this->assertNotNull($mapped);
        $this->assertEquals(5001, $mapped['nro_venta']);
    }

    public function test_falls_back_fecha_from_context(): void
    {
        $row = [
            'nro_venta' => 6001,
            // No fecha field
        ];

        $context = [
            'date_init' => '2025-05-01',
        ];

        $mapped = $this->mapper->map($row, $context);

        $this->assertEquals('2025-05-01', $mapped['fecha_venta']);
    }

    public function test_maps_all_payment_methods(): void
    {
        $row = [
            'nro_venta' => 7001,
            'fecha_venta' => '2025-06-01',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $row["fp{$i}"] = $i * 100.0;
        }

        $mapped = $this->mapper->map($row);

        for ($i = 1; $i <= 10; $i++) {
            $this->assertEquals($i * 100.0, $mapped["fp{$i}"]);
        }
    }

    public function test_maps_all_article_fields(): void
    {
        $row = [
            'nro_venta' => 8001,
            'fecha_venta' => '2025-07-01',
        ];

        for ($i = 1; $i <= 12; $i++) {
            $row["art{$i}"] = $i * 50.0;
        }

        $mapped = $this->mapper->map($row);

        for ($i = 1; $i <= 12; $i++) {
            $this->assertEquals($i * 50.0, $mapped["art{$i}"]);
        }
    }
}
