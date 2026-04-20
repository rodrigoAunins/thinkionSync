<?php

namespace App\Services\Thinkion\Mappers;

use App\Services\Thinkion\Contracts\ReportMapperInterface;

/**
 * Mapper para el detalle de ventas / performance de productos.
 */
class ProductMixReportMapper implements ReportMapperInterface
{
    public function map(array $row, array $context = []): ?array
    {
        // Basado en el esquema de vinson_product_mix (MySQL)
        return [
            'store_id'   => $row['establishment'] ?? $context['establishment'] ?? '1',
            'date'       => $row['date'] ?? $row['date_init'] ?? $context['date_init'] ?? now()->toDateString(),
            'idProducto' => $row['id_product'] ?? $row['id_article'] ?? $row['code_external'] ?? null,
            'codigo'     => $row['code'] ?? $row['sku'] ?? $row['id_product'] ?? null,
            'producto'   => $row['name'] ?? $row['producto'] ?? $row['description'] ?? 'Producto Desconocido',
            'cantidad'   => $row['quantity'] ?? $row['q_orders'] ?? 0,
            'importe'    => $row['total'] ?? $row['amount'] ?? 0,
            'synced_at'  => now(),
        ];
    }
}
