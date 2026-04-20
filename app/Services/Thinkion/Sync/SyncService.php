<?php

namespace App\Services\Thinkion\Sync;

use App\Enums\SyncStatus;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Raw\RawReportRepository;
use App\Repositories\Raw\SyncRunRepository;
use App\Services\Thinkion\ApiClient;
use App\Services\Thinkion\Contracts\ReportMapperInterface;
use App\Services\Thinkion\Reports\ReportDefinition;
use App\Services\Thinkion\Support\DateRangeChunker;
use App\Services\Thinkion\Support\SyncLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class SyncService
{
    private ApiClient $apiClient;
    private SyncRunRepository $syncRunRepo;
    private RawReportRepository $rawRepo;

    public function __construct(
        ApiClient $apiClient,
        SyncRunRepository $syncRunRepo,
        RawReportRepository $rawRepo
    ) {
        $this->apiClient = $apiClient;
        $this->syncRunRepo = $syncRunRepo;
        $this->rawRepo = $rawRepo;
    }

    /**
     * Synchronize a single report across a date range, handling:
     * - Date range chunking (≤30 days per API call)
     * - Pagination (automatic via ApiClient)
     * - Mapping (via registered mapper)
     * - Upsert persistence (via registered repository)
     * - Raw data persistence (always)
     * - Metrics tracking (thinkion_sync_runs)
     */
    public function syncReport(
        ReportDefinition $report,
        Carbon $dateInit,
        Carbon $dateEnd,
        array $establishments
    ): array {
        $maxDays = config('thinkion.api.max_days_per_request', 30);
        $chunks = DateRangeChunker::chunk($dateInit, $dateEnd, $maxDays);

        SyncLogger::logInfo("Starting sync for report {$report->reportId} ({$report->name})", [
            'date_range' => "{$dateInit->format('Y-m-d')} → {$dateEnd->format('Y-m-d')}",
            'chunks' => count($chunks),
            'establishments' => $establishments,
        ]);

        $run = $this->syncRunRepo->startRun($report->reportId, $report->name, [
            'date_init' => $dateInit->format('Y-m-d'),
            'date_end' => $dateEnd->format('Y-m-d'),
            'establishments' => $establishments,
            'chunks' => count($chunks),
        ]);

        $totals = [
            'rows_received' => 0,
            'rows_inserted' => 0,
            'rows_updated' => 0,
            'rows_skipped' => 0,
            'rows_failed' => 0,
            'raw_stored' => 0,
            'chunks_processed' => 0,
        ];

        /** @var ReportMapperInterface $mapper */
        $mapper = App::make($report->mapperClass);

        /** @var DomainRepositoryInterface|null $domainRepo */
        $domainRepo = null;
        if ($report->repositoryClass) {
            $domainRepo = App::make($report->repositoryClass);
        }

        try {
            foreach ($chunks as $chunk) {
                $chunkStart = $chunk['start']->format('Y-m-d');
                $chunkEnd = $chunk['end']->format('Y-m-d');

                SyncLogger::logInfo("Processing chunk: {$chunkStart} → {$chunkEnd}");

                try {
                    $rows = $this->apiClient->fetchAllPages(
                        $report->reportId,
                        $chunkStart,
                        $chunkEnd,
                        $establishments
                    );
                } catch (\Throwable $e) {
                    SyncLogger::logError("API fetch failed for chunk {$chunkStart} → {$chunkEnd}: {$e->getMessage()}");
                    $totals['rows_failed']++;
                    continue;
                }

                $totals['rows_received'] += count($rows);
                $totals['chunks_processed']++;

                // Process rows in batches for memory efficiency
                $batchSize = 100;
                $rowChunks = array_chunk($rows, $batchSize);

                foreach ($rowChunks as $rowBatch) {
                    foreach ($rowBatch as $row) {
                        if (empty($row) || !is_array($row)) {
                            $totals['rows_skipped']++;
                            continue;
                        }

                        try {
                            // 1. Always store raw data
                            $this->storeRawData($row, $report, $chunkStart, $chunkEnd);
                            $totals['raw_stored']++;
                        } catch (\Throwable $e) {
                            SyncLogger::logWarning("[RAW_STORE_FAILED] {$e->getMessage()}");
                        }

                        try {
                            // 2. Map to domain model
                            $context = [
                                'report_id' => $report->reportId,
                                'report_name' => $report->name,
                                'date_init' => $chunkStart,
                                'date_end' => $chunkEnd,
                            ];

                            $mapped = $mapper->map($row, $context);

                            if ($mapped === null) {
                                $totals['rows_skipped']++;
                                continue;
                            }

                            // 3. Persist to domain table
                            if ($domainRepo) {
                                $model = DB::transaction(fn() => $domainRepo->upsert($mapped));

                                if ($model->wasRecentlyCreated) {
                                    $totals['rows_inserted']++;
                                } else {
                                    $totals['rows_updated']++;
                                }
                            } else {
                                $totals['rows_skipped']++;
                            }
                        } catch (\Throwable $e) {
                            SyncLogger::logWarning("[DOMAIN_PERSIST_FAILED] {$e->getMessage()}", [
                                'row_keys' => array_keys($row),
                            ]);
                            $totals['rows_failed']++;
                        }
                    }
                }
            }

            $status = ($totals['rows_failed'] > 0)
                ? SyncStatus::COMPLETED_WITH_ERRORS
                : SyncStatus::COMPLETED;

            $this->syncRunRepo->finishRun($run, $status, $totals);

            SyncLogger::logInfo("Sync completed for report {$report->reportId}", $totals);

            return $totals;

        } catch (\Throwable $e) {
            SyncLogger::logError("Sync FAILED for report {$report->reportId}: {$e->getMessage()}");
            $this->syncRunRepo->finishRun($run, SyncStatus::FAILED, $totals, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store a raw API row in the staging table.
     */
    private function storeRawData(array $row, ReportDefinition $report, string $dateInit, string $dateEnd): void
    {
        $this->rawRepo->upsert([
            'report_id' => $report->reportId,
            'report_name' => $report->name,
            'external_id' => $row['id'] ?? $row['Id'] ?? $row['ID'] ?? null,
            'payload' => $row,
            'date_init' => $dateInit,
            'date_end' => $dateEnd,
            'fetched_at' => now(),
        ]);
    }
}
