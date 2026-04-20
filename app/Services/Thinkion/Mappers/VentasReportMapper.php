<?php

namespace App\Services\Thinkion\Mappers;

use App\Services\Thinkion\Contracts\ReportMapperInterface;
use App\Services\Thinkion\Support\SyncLogger;

/**
 * Mapper para reportes que alimentan la tabla `ventas`.
 *
 * Este mapper es configurable: el mapeo de columnas API → columnas ventas
 * se define en el array $columnMap. Si la estructura de los datos de
 * Thinkion cambia, solo hay que actualizar este mapeo.
 *
 * NOTA: Como no tenemos documentación exacta de las columnas del reporte,
 * este mapper busca coincidencias automáticas por nombre de campo y también
 * permite un mapeo manual explícito.
 */
class VentasReportMapper implements ReportMapperInterface
{
    /**
     * Mapeo explícito: clave API → columna en tabla ventas.
     *
     * Ajustar según la estructura real devuelta por la API de Thinkion.
     * Las claves son case-insensitive en la búsqueda.
     */
    protected array $columnMap = [
        // Campos principales
        'id_sale' => 'nro_venta',
        'nro_venta' => 'nro_venta',
        'numero_venta' => 'nro_venta',
        'transaction_number' => 'nro_venta',
        'indice_venta' => 'indice_venta',
        'fecha_venta' => 'fecha_venta',
        'close_date' => 'fecha_venta',
        'fecha' => 'fecha_venta',
        'date' => 'fecha_venta',
        'turno' => 'turno',
        'shift' => 'turno',
        'clima' => 'clima',
        'users_id' => 'users_id',
        'user_id' => 'users_id',

        // Montos principales
        'ingresos' => 'ingresos',
        'income' => 'ingresos',
        'anulaciones' => 'anulaciones',
        'cancellations' => 'anulaciones',
        'egresos' => 'egresos',
        'expenses' => 'egresos',
        'ventas_fiscal' => 'ventas_fiscal',
        'fiscal_sales' => 'ventas_fiscal',
        'ventas_no_fiscal' => 'ventas_no_fiscal',
        'non_fiscal_sales' => 'ventas_no_fiscal',

        // Formas de pago
        'fp1' => 'fp1', 'fp2' => 'fp2', 'fp3' => 'fp3', 'fp4' => 'fp4',
        'fp5' => 'fp5', 'fp6' => 'fp6', 'fp7' => 'fp7', 'fp8' => 'fp8',
        'fp9' => 'fp9', 'fp10' => 'fp10',

        // Ventas por categoría
        'venta_alimentos' => 'venta_alimentos',
        'food_sales' => 'venta_alimentos',
        'venta_bebidas' => 'venta_bebidas',
        'beverage_sales' => 'venta_bebidas',

        // Artículos
        'art1' => 'art1', 'art2' => 'art2', 'art3' => 'art3', 'art4' => 'art4',
        'art5' => 'art5', 'art6' => 'art6', 'art7' => 'art7', 'art8' => 'art8',
        'art9' => 'art9', 'art10' => 'art10', 'art11' => 'art11', 'art12' => 'art12',

        // Otros
        'obs' => 'obs',
        'observations' => 'obs',
        'caja' => 'caja',
        'box' => 'caja',
        'estado_venta' => 'estado_venta',
        'sale_status' => 'estado_venta',
        'derecho_show' => 'derecho_show',
        'dshowId' => 'dshowId',
        'cuenta_corriente' => 'cuenta_corriente',
        'indice_pinta' => 'indice_pinta',
        'diferenciaCaja' => 'diferenciaCaja',
        'valor_studio_caja_chica' => 'valor_studio_caja_chica',
        'valor_bosque_caja_chica' => 'valor_bosque_caja_chica',
    ];

    public function map(array $row, array $context = []): ?array
    {
        if (empty($row)) {
            return null;
        }

        $mapped = [];
        $rowLower = array_change_key_case($row, CASE_LOWER);

        foreach ($this->columnMap as $apiKey => $dbColumn) {
            $apiKeyLower = strtolower($apiKey);
            if (array_key_exists($apiKeyLower, $rowLower)) {
                // Only set if not already set (first match wins — keeps priority)
                if (!isset($mapped[$dbColumn])) {
                    $mapped[$dbColumn] = $rowLower[$apiKeyLower];
                }
            }
        }

        // Si no se pudo mapear nro_venta o fecha_venta, intentar extracción flexible
        if (empty($mapped['nro_venta'])) {
            // Try the first numeric-looking field as fallback
            $mapped['nro_venta'] = $row['id'] ?? $row['Id'] ?? $row['ID'] ?? null;
        }

        if (empty($mapped['fecha_venta']) && !empty($context['date_init'])) {
            $mapped['fecha_venta'] = $context['date_init'];
        }

        // Defaults para campos requeridos por el esquema legacy de la base de datos de negocio
        $mapped['indice_venta'] = $mapped['indice_venta'] ?? 0;
        $mapped['turno'] = $mapped['turno'] ?? 1;
        $mapped['clima'] = $mapped['clima'] ?? 1;
        $mapped['users_id'] = $mapped['users_id'] ?? 1;
        $mapped['egresos'] = $mapped['egresos'] ?? 0;
        $mapped['caja'] = $mapped['caja'] ?? 1;
        $mapped['estado_venta'] = $mapped['estado_venta'] ?? 1;
        $mapped['ingresos'] = $mapped['ingresos'] ?? $row['total'] ?? 0;

        // Validar campos mínimos requeridos
        if (empty($mapped['nro_venta'])) {
            SyncLogger::logWarning("VentasReportMapper: fila sin nro_venta identificable, skipping", [
                'row_keys' => array_keys($row),
            ]);
            return null;
        }

        return $mapped;
    }
}
