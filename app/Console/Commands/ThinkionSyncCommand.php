<?php

namespace App\Console\Commands;

use App\Enums\ReportType;
use App\Services\Thinkion\Sync\SyncOrchestrator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ThinkionSyncCommand extends Command
{
    protected $signature = 'thinkion:sync
                            {--report= : ID de reporte específico a sincronizar (ej: 233)}
                            {--resource= : Filtrar por tipo de recurso: transaction, sales, products, generic}
                            {--start= : Fecha inicio (Y-m-d). Default: hace N días según config}
                            {--end= : Fecha fin (Y-m-d). Default: hoy}
                            {--establishments= : IDs de establecimientos separados por coma (ej: 1,2,3)}';

    protected $description = 'Sincronizar datos desde la API de Thinkion. Permite filtrar por reporte, recurso y rango de fechas.';

    public function handle(SyncOrchestrator $orchestrator): int
    {
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║           Thinkion Sync - Iniciando              ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        // Resolve dates
        $daysBack = config('thinkion.sync.days_back', 7);
        $dateEnd = $this->option('end')
            ? Carbon::parse($this->option('end'))
            : Carbon::today();
        $dateInit = $this->option('start')
            ? Carbon::parse($this->option('start'))
            : $dateEnd->copy()->subDays($daysBack);

        // Resolve establishments
        $establishments = $this->option('establishments')
            ? array_map('intval', explode(',', $this->option('establishments')))
            : config('thinkion.sync.default_establishments', [1, 2]);

        $this->info("📅 Rango: {$dateInit->format('Y-m-d')} → {$dateEnd->format('Y-m-d')}");
        $this->info("🏪 Establecimientos: " . implode(', ', $establishments));

        try {
            // Mode 1: Specific report
            if ($reportId = $this->option('report')) {
                $this->info("📊 Sincronizando reporte #{$reportId}...");
                $results = $orchestrator->syncReport(
                    (int) $reportId,
                    $dateInit,
                    $dateEnd,
                    $establishments
                );
                $this->displayResults([(int) $reportId => $results]);
                return self::SUCCESS;
            }

            // Mode 2: By resource type
            if ($resource = $this->option('resource')) {
                // Normalizar plurales a singular
                $normalized = match(strtolower($resource)) {
                    'transactions' => 'transaction',
                    'sales'        => 'sales',
                    'products'     => 'products',
                    default        => $resource
                };

                $type = ReportType::tryFrom($normalized);
                if (!$type) {
                    $this->error("Tipo de recurso inválido: {$resource}. Opciones: transaction, sales, products, generic");
                    return self::FAILURE;
                }
                $this->info("📊 Sincronizando reportes de tipo '{$normalized}'...");
                $results = $orchestrator->syncByType($type, $dateInit, $dateEnd, $establishments);
                $this->displayResults($results);
                return self::SUCCESS;
            }

            // Mode 3: All registered reports
            $this->info("📊 Sincronizando TODOS los reportes registrados...");
            $results = $orchestrator->syncAll($dateInit, $dateEnd, $establishments);
            $this->displayResults($results);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error de sincronización: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('═══════════════════ RESULTADOS ═══════════════════');

        foreach ($results as $reportId => $totals) {
            if (isset($totals['error'])) {
                $this->error("  Reporte #{$reportId}: ERROR — {$totals['error']}");
                continue;
            }

            $this->info("  Reporte #{$reportId}:");
            $this->line("    Recibidos:   " . ($totals['rows_received'] ?? 0));
            $this->line("    Insertados:  " . ($totals['rows_inserted'] ?? 0));
            $this->line("    Actualizados:" . ($totals['rows_updated'] ?? 0));
            $this->line("    Omitidos:    " . ($totals['rows_skipped'] ?? 0));
            $this->line("    Fallidos:    " . ($totals['rows_failed'] ?? 0));
            $this->line("    Raw almac.:  " . ($totals['raw_stored'] ?? 0));
        }

        $this->info('══════════════════════════════════════════════════');
    }
}
