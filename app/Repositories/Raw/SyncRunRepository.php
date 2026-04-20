<?php

namespace App\Repositories\Raw;

use App\Enums\SyncStatus;
use App\Models\SyncRun;

class SyncRunRepository
{
    /**
     * Create a new sync run record (status = RUNNING).
     */
    public function startRun(int $reportId, string $reportName, array $context = []): SyncRun
    {
        return SyncRun::create([
            'report_id' => $reportId,
            'report_name' => $reportName,
            'status' => SyncStatus::RUNNING,
            'started_at' => now(),
            'context_json' => $context,
        ]);
    }

    /**
     * Mark a sync run as finished with final status and totals.
     */
    public function finishRun(
        SyncRun $run,
        SyncStatus $status,
        array $totals = [],
        ?string $errorMessage = null
    ): SyncRun {
        $run->update([
            'status' => $status,
            'finished_at' => now(),
            'totals_json' => $totals,
            'error_message' => $errorMessage,
        ]);

        return $run;
    }
}
