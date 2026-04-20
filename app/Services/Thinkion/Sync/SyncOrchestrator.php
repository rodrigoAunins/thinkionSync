<?php

namespace App\Services\Thinkion\Sync;

use App\Enums\ReportType;
use App\Services\Thinkion\Reports\ReportRegistry;
use App\Services\Thinkion\Support\SyncLogger;
use Carbon\Carbon;

class SyncOrchestrator
{
    private ReportRegistry $registry;
    private SyncService $syncService;

    public function __construct(ReportRegistry $registry, SyncService $syncService)
    {
        $this->registry = $registry;
        $this->syncService = $syncService;
    }

    /**
     * Sync a specific report by ID.
     */
    public function syncReport(
        int $reportId,
        Carbon $dateInit,
        Carbon $dateEnd,
        array $establishments
    ): array {
        $report = $this->registry->getReportOrGeneric($reportId);

        return $this->syncService->syncReport($report, $dateInit, $dateEnd, $establishments);
    }

    /**
     * Sync all registered reports.
     */
    public function syncAll(Carbon $dateInit, Carbon $dateEnd, array $establishments): array
    {
        $results = [];

        foreach ($this->registry->all() as $report) {
            SyncLogger::logInfo("═══ Orchestrator: Syncing report {$report->reportId} ({$report->name}) ═══");

            try {
                $results[$report->reportId] = $this->syncService->syncReport(
                    $report,
                    $dateInit,
                    $dateEnd,
                    $establishments
                );
            } catch (\Throwable $e) {
                SyncLogger::logError("Orchestrator: Report {$report->reportId} failed: {$e->getMessage()}");
                $results[$report->reportId] = [
                    'error' => $e->getMessage(),
                    'rows_received' => 0,
                    'rows_failed' => 1,
                ];
            }
        }

        return $results;
    }

    /**
     * Sync all reports of a specific type (e.g., TRANSACTION, PRODUCTS).
     */
    public function syncByType(
        ReportType $type,
        Carbon $dateInit,
        Carbon $dateEnd,
        array $establishments
    ): array {
        $results = [];
        $reports = $this->registry->getByType($type);

        if (empty($reports)) {
            SyncLogger::logWarning("No reports registered for type: {$type->value}");
            return $results;
        }

        foreach ($reports as $report) {
            try {
                $results[$report->reportId] = $this->syncService->syncReport(
                    $report,
                    $dateInit,
                    $dateEnd,
                    $establishments
                );
            } catch (\Throwable $e) {
                SyncLogger::logError("Orchestrator: Report {$report->reportId} failed: {$e->getMessage()}");
                $results[$report->reportId] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Sync specific report IDs (from config or CLI args).
     */
    public function syncReportIds(
        array $reportIds,
        Carbon $dateInit,
        Carbon $dateEnd,
        array $establishments
    ): array {
        $results = [];

        foreach ($reportIds as $reportId) {
            try {
                $results[$reportId] = $this->syncReport(
                    (int) $reportId,
                    $dateInit,
                    $dateEnd,
                    $establishments
                );
            } catch (\Throwable $e) {
                SyncLogger::logError("Orchestrator: Report {$reportId} failed: {$e->getMessage()}");
                $results[$reportId] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
