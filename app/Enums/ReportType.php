<?php

namespace App\Enums;

/**
 * Tipos de recurso lógico al que mapea un reporte de Thinkion.
 * Permite filtrar qué reportes sincronizar por categoría.
 */
enum ReportType: string
{
    case TRANSACTION = 'transaction';
    case SALES = 'sales';
    case PRODUCTS = 'products';
    case GENERIC = 'generic';
}
