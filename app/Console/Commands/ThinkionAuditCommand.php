<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Enums\ReportType;
use App\Services\Thinkion\Reports\ReportRegistry;

class ThinkionAuditCommand extends Command
{
    protected $signature = 'thinkion:audit 
                            {--limit=10 : Cantidad de ejecuciones a mostrar}
                            {--resource= : Filtrar por recurso (transaction, sales, products)}';
    protected $description = 'Mostrar historial de auditoría de las sincronizaciones desde la base de datos.';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║       Thinkion ETL - Historial de Auditoría      ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        $limit = $this->option('limit');
        $resource = $this->option('resource');

        $query = DB::table('thinkion_thinkion_sync_runs')
            ->orderBy('started_at', 'desc')
            ->limit((int)$limit);

        if ($resource) {
            // Normalizar alias
            $normalized = match(strtolower($resource)) {
                'transactions' => 'transaction',
                'sales'        => 'sales',
                'products'     => 'products',
                default        => $resource
            };

            $type = ReportType::tryFrom($normalized);
            if ($type) {
                // Obtener IDs de reporte para este tipo desde el Registry
                $registry = app(ReportRegistry::class);
                $reportIds = [];
                foreach ($registry->all() as $def) {
                    if ($def->type === $type) {
                        $reportIds[] = $def->reportId;
                    }
                }
                
                if (!empty($reportIds)) {
                    $query->whereIn('report_id', $reportIds);
                    $this->info("💡 Filtrando por recurso: {$normalized}");
                }
            }
        }

        $runs = $query->get();

        if ($runs->isEmpty()) {
            $this->warn('No se encontraron registros de sincronización en la base de datos.');
            return self::SUCCESS;
        }

        $headers = ['ID', 'Reporte', 'Estado', 'Inicio', 'Fin', 'Total'];
        $data = $runs->map(function ($run) {
            $totals = json_decode($run->totals_json ?? '{}', true);
            $totalCount = ($totals['rows_inserted'] ?? 0) + ($totals['rows_updated'] ?? 0);
            
            return [
                $run->id,
                $run->report_name . " (#{$run->report_id})",
                $this->formatStatus($run->status),
                $run->started_at,
                $run->finished_at ?? '-',
                $totalCount . " filas",
            ];
        });

        $this->table($headers, $data);

        if ($this->confirm('¿Desea ver el detalle de la última ejecución?', true)) {
            $this->showDetail($runs->first());
        }

        return self::SUCCESS;
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'completed' => '✅ COMPLETO',
            'failed'    => '❌ FALLIDO',
            'running'   => '⏳ CORRIENDO',
            default     => '❓ ' . strtoupper($status),
        };
    }

    private function showDetail($run): void
    {
        $this->newLine();
        $this->info("🔍 Detalle Ejecución #{$run->id}");
        $this->line("--------------------------------------------------");
        $this->line("📅 Fecha: " . $run->started_at);
        $this->line("📊 Reporte: {$run->report_name} (#{$run->report_id})");
        
        $totals = json_decode($run->totals_json ?? '{}', true);
        $this->line("✅ Insertados:  " . ($totals['rows_inserted'] ?? 0));
        $this->line("🔄 Actualizados: " . ($totals['rows_updated'] ?? 0));
        $this->line("⚠️ Fallidos:     " . ($totals['rows_failed'] ?? 0));
        $this->line("📦 Raw Stored:  " . ($totals['raw_stored'] ?? 0));

        if ($run->error_message) {
            $this->error("❌ ERROR: " . $run->error_message);
        }

        $this->line("--------------------------------------------------");
    }
}
