<?php

namespace App\Services\Thinkion\Contracts;

interface ReportMapperInterface
{
    /**
     * Map a single row from the Thinkion API response to the target table structure.
     *
     * @param array $row     A single row from the API's "data" array
     * @param array $context Additional context (report_id, date range, etc.)
     * @return array|null    Mapped data ready for persistence, or null to skip
     */
    public function map(array $row, array $context = []): ?array;
}
