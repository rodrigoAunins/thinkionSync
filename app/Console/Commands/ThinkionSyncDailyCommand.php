<?php

namespace App\Console\Commands;

use App\Services\Thinkion\Sync\SyncOrchestrator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ThinkionSyncDailyCommand extends Command
{
    protected $signature = 'thinkion:sync-daily';

    protected $description = 'Sincronización diaria automática. Usa los valores por defecto del .env (reportes, establecimientos, días hacia atrás).';

    public function handle(SyncOrchestrator $orchestrator): int
    {
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║        Thinkion Daily Sync - Automático          ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        $daysBack = config('thinkion.sync.days_back', 7);
        $dateEnd = Carbon::today();
        $dateInit = $dateEnd->copy()->subDays($daysBack);
        $establishments = config('thinkion.sync.default_establishments', [1, 2]);
        $reportIds = config('thinkion.sync.default_report_ids', [233]);

        $this->info("📅 Rango: {$dateInit->format('Y-m-d')} → {$dateEnd->format('Y-m-d')}");
        $this->info("🏪 Establecimientos: " . implode(', ', $establishments));
        $this->info("📊 Reportes: " . implode(', ', $reportIds));

        try {
            $results = $orchestrator->syncReportIds(
                $reportIds,
                $dateInit,
                $dateEnd,
                $establishments
            );

            $this->newLine();
            $totalInserted = 0;
            $totalUpdated = 0;
            $totalFailed = 0;

            foreach ($results as $reportId => $totals) {
                if (isset($totals['error'])) {
                    $this->error("  Reporte #{$reportId}: ERROR — {$totals['error']}");
                    $totalFailed++;
                    continue;
                }

                $inserted = $totals['rows_inserted'] ?? 0;
                $updated = $totals['rows_updated'] ?? 0;
                $this->info("  ✅ Reporte #{$reportId}: {$inserted} nuevos, {$updated} actualizados");
                $totalInserted += $inserted;
                $totalUpdated += $updated;
            }

            $this->newLine();
            $this->info("📊 Totales: {$totalInserted} insertados, {$totalUpdated} actualizados, {$totalFailed} con error");

            return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error crítico en sync diario: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
