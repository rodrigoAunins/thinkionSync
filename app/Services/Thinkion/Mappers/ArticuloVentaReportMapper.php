<?php

namespace App\Services\Thinkion\Mappers;

use App\Services\Thinkion\Contracts\ReportMapperInterface;

/**
 * Mapper para el catálogo de artículos (Reporte de Productos).
 */
class ArticuloVentaReportMapper implements ReportMapperInterface
{
    public function map(array $row, array $context = []): ?array
    {
        // Basado en el esquema de articulo_ventas (MySQL)
        return [
            'id'          => $row['id'] ?? $row['Id'] ?? $row['id_sale'] ?? null,
            'descripcion' => $row['name'] ?? $row['producto'] ?? $row['description'] ?? 'Producto: ' . ($row['id_sale'] ?? 'N/A'),
            'estado'      => 1,
            'tipo'        => 1,
        ];
    }
}
