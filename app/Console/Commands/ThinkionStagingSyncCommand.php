<?php

namespace App\Console\Commands;

use App\Services\Thinkion\Sync\SyncOrchestrator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ThinkionStagingSyncCommand extends Command
{
    protected $signature = 'thinkion:sync-staging
                            {report_id : ID del reporte de Thinkion}
                            {--start= : Fecha inicio (Y-m-d)}
                            {--end= : Fecha fin (Y-m-d)}
                            {--establishments= : IDs de establecimientos (ej: 1,2)}';

    protected $description = 'Sincronizar datos crudos (Staging) de cualquier reporte a la tabla thinkion_raw_reports.';

    public function handle(SyncOrchestrator $orchestrator): int
    {
        $reportId = (int) $this->argument('report_id');
        $dateEnd = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::today();
        $dateInit = $this->option('start') ? Carbon::parse($this->option('start')) : $dateEnd->copy()->subDays(7);
        
        $establishments = $this->option('establishments')
            ? array_map('intval', explode(',', $this->option('establishments')))
            : config('thinkion.sync.default_establishments', [1, 2]);

        $this->info("🚀 Iniciando captura en Staging (Raw) para el reporte #{$reportId}");
        $this->line("📅 Rango: {$dateInit->format('Y-m-d')} → {$dateEnd->format('Y-m-d')}");

        try {
            // Sincronizar reporte (el orquestador decidirá usar GenericMapper si no está registrado con uno específico)
            $results = $orchestrator->syncReport($reportId, $dateInit, $dateEnd, $establishments);

            $this->newLine();
            $this->info('✅ Captura completada:');
            $this->line("📦 Registros guardados en Raw: " . ($results['raw_stored'] ?? 0));
            $this->line("⚠️ Fallidos: " . ($results['rows_failed'] ?? 0));
            $this->line("🔍 Revisa la tabla 'thinkion_raw_reports' en Postgres para ver los datos crudos.");
            
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
