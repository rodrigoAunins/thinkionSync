<?php

namespace Tests\Unit;

use App\Services\Thinkion\Mappers\GenericReportMapper;
use Tests\TestCase;

class GenericReportMapperTest extends TestCase
{
    private GenericReportMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new GenericReportMapper();
    }

    public function test_maps_row_with_context(): void
    {
        $row = ['field1' => 'value1', 'field2' => 123];
        $context = [
            'report_id' => 233,
            'report_name' => 'test_report',
            'date_init' => '2025-01-01',
            'date_end' => '2025-01-31',
        ];

        $mapped = $this->mapper->map($row, $context);

        $this->assertNotNull($mapped);
        $this->assertEquals(233, $mapped['report_id']);
        $this->assertEquals('test_report', $mapped['report_name']);
        $this->assertEquals($row, $mapped['payload']);
        $this->assertEquals('2025-01-01', $mapped['date_init']);
    }

    public function test_skips_empty_rows(): void
    {
        $this->assertNull($this->mapper->map([]));
    }

    public function test_extracts_external_id(): void
    {
        $row = ['id' => 999, 'data' => 'test'];
        $mapped = $this->mapper->map($row);

        $this->assertEquals(999, $mapped['external_id']);
    }

    public function test_handles_missing_context(): void
    {
        $row = ['name' => 'test'];
        $mapped = $this->mapper->map($row);

        $this->assertNotNull($mapped);
        $this->assertNull($mapped['report_id']);
        $this->assertEquals('unknown', $mapped['report_name']);
    }
}
