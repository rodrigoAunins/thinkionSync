<?php

namespace Tests\Unit;

use App\Services\Thinkion\Support\DateRangeChunker;
use Carbon\Carbon;
use Tests\TestCase;

class DateRangeChunkerTest extends TestCase
{
    public function test_single_day_returns_one_chunk(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-01');

        $chunks = DateRangeChunker::chunk($start, $end);

        $this->assertCount(1, $chunks);
        $this->assertEquals('2025-01-01', $chunks[0]['start']->format('Y-m-d'));
        $this->assertEquals('2025-01-01', $chunks[0]['end']->format('Y-m-d'));
    }

    public function test_within_30_days_returns_one_chunk(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-25');

        $chunks = DateRangeChunker::chunk($start, $end, 30);

        $this->assertCount(1, $chunks);
        $this->assertEquals('2025-01-01', $chunks[0]['start']->format('Y-m-d'));
        $this->assertEquals('2025-01-25', $chunks[0]['end']->format('Y-m-d'));
    }

    public function test_exactly_30_days_returns_one_chunk(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-30');

        $chunks = DateRangeChunker::chunk($start, $end, 30);

        $this->assertCount(1, $chunks);
    }

    public function test_31_days_returns_two_chunks(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-02-01'); // 31 days diff

        $chunks = DateRangeChunker::chunk($start, $end, 30);

        $this->assertCount(2, $chunks);
        $this->assertEquals('2025-01-01', $chunks[0]['start']->format('Y-m-d'));
        $this->assertEquals('2025-01-30', $chunks[0]['end']->format('Y-m-d'));
        $this->assertEquals('2025-01-31', $chunks[1]['start']->format('Y-m-d'));
        $this->assertEquals('2025-02-01', $chunks[1]['end']->format('Y-m-d'));
    }

    public function test_90_days_returns_three_chunks(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-03-31');

        $chunks = DateRangeChunker::chunk($start, $end, 30);

        $this->assertCount(3, $chunks);

        // Verify no gaps between chunks
        for ($i = 1; $i < count($chunks); $i++) {
            $prevEnd = $chunks[$i - 1]['end'];
            $currStart = $chunks[$i]['start'];
            $this->assertEquals(1, $prevEnd->diffInDays($currStart));
        }

        // Verify last chunk ends on the actual end date
        $this->assertEquals('2025-03-31', end($chunks)['end']->format('Y-m-d'));
    }

    public function test_custom_max_days(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-20');

        $chunks = DateRangeChunker::chunk($start, $end, 7);

        $this->assertCount(3, $chunks);
    }

    public function test_start_after_end_throws_exception(): void
    {
        $start = Carbon::parse('2025-02-01');
        $end = Carbon::parse('2025-01-01');

        $this->expectException(\InvalidArgumentException::class);
        DateRangeChunker::chunk($start, $end);
    }

    public function test_chunks_do_not_overlap(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-06-30');

        $chunks = DateRangeChunker::chunk($start, $end, 30);

        for ($i = 1; $i < count($chunks); $i++) {
            $this->assertTrue(
                $chunks[$i]['start']->gt($chunks[$i - 1]['end']),
                "Chunk {$i} overlaps with chunk " . ($i - 1)
            );
        }
    }

    public function test_all_dates_covered(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-03-15');

        $chunks = DateRangeChunker::chunk($start, $end, 30);

        $this->assertEquals('2025-01-01', $chunks[0]['start']->format('Y-m-d'));
        $this->assertEquals('2025-03-15', end($chunks)['end']->format('Y-m-d'));
    }
}
