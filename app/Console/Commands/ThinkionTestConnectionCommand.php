<?php

namespace App\Console\Commands;

use App\Services\Thinkion\ApiClient;
use Illuminate\Console\Command;

class ThinkionTestConnectionCommand extends Command
{
    protected $signature = 'thinkion:test-connection
                            {--report=233 : ID de reporte para probar}
                            {--establishments=1,2 : IDs de establecimientos}';

    protected $description = 'Verificar conectividad con la API de Thinkion. Hace una llamada de prueba y muestra el resultado.';

    public function handle(ApiClient $apiClient): int
    {
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║       Thinkion - Test de Conexión                ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        $reportId = (int) $this->option('report');
        $establishments = array_map('intval', explode(',', $this->option('establishments')));

        $this->info("🌐 URL Base: {$apiClient->getBaseUrl()}");
        $this->info("📊 Reporte de prueba: #{$reportId}");
        $this->info("🏪 Establecimientos: " . implode(', ', $establishments));
        $this->newLine();

        try {
            $this->info("⏳ Conectando...");
            $result = $apiClient->testConnection($reportId, $establishments);

            $dataCount = count($result['data'] ?? []);
            $hasPage = !empty($result['page']);

            $this->newLine();
            $this->info("✅ ¡Conexión exitosa!");
            $this->info("   Datos recibidos: {$dataCount} filas");
            $this->info("   Paginación: " . ($hasPage ? "Sí (hay más páginas)" : "No"));

            if ($dataCount > 0) {
                $this->newLine();
                $this->info("📋 Muestra del primer registro:");
                $firstRow = $result['data'][0];
                $this->line("   Campos: " . implode(', ', array_keys($firstRow)));
                $this->newLine();

                // Show first row as table
                $tableData = [];
                foreach ($firstRow as $key => $value) {
                    $displayValue = is_array($value) ? json_encode($value) : (string) $value;
                    if (strlen($displayValue) > 60) {
                        $displayValue = substr($displayValue, 0, 57) . '...';
                    }
                    $tableData[] = [$key, $displayValue];
                }
                $this->table(['Campo', 'Valor'], $tableData);
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("❌ Error de conexión: {$e->getMessage()}");

            if ($e instanceof \App\Exceptions\ThinkionApiException) {
                $this->error("   HTTP Status: {$e->getHttpStatus()}");
                $body = $e->getResponseBody();
                if ($body) {
                    $this->error("   Response: " . substr($body, 0, 500));
                }
            }

            $this->newLine();
            $this->warn("💡 Verificar:");
            $this->warn("   - THINKION_CLIENT_CODE en .env");
            $this->warn("   - THINKION_API_TOKEN en .env");
            $this->warn("   - Conectividad de red");

            return self::FAILURE;
        }
    }
}
