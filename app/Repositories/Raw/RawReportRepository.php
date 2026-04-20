<?php

namespace App\Repositories\Raw;

use App\Models\ThinkionRawReport;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Services\Thinkion\Support\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class RawReportRepository implements DomainRepositoryInterface
{
    /**
     * Upsert a raw report row.
     * Match key: report_id + external_id (to avoid duplicates).
     */
    public function upsert(array $data): Model
    {
        $externalId = $data['external_id'] ?? null;
        $reportId = $data['report_id'] ?? null;

        // If we have a unique external ID, use updateOrCreate
        if ($externalId !== null && $reportId !== null) {
            $match = [
                'report_id' => $reportId,
                'external_id' => $externalId,
            ];

            $record = ThinkionRawReport::updateOrCreate($match, $data);
        } else {
            // No unique key — just insert (for truly raw data)
            $record = ThinkionRawReport::create($data);
        }

        SyncLogger::logInfo("[RAW_REPO] " . ($record->wasRecentlyCreated ? 'INSERTED' : 'UPDATED'), [
            'id' => $record->id,
            'report_id' => $reportId,
        ]);

        return $record;
    }
}
