<?php

namespace App\Services\Thinkion\Mappers;

use App\Services\Thinkion\Contracts\ReportMapperInterface;

/**
 * Mapper genérico que guarda la fila cruda tal cual viene del API.
 * Se usa como fallback para reportes sin mapper específico.
 */
class GenericReportMapper implements ReportMapperInterface
{
    public function map(array $row, array $context = []): ?array
    {
        if (empty($row)) {
            return null;
        }

        return [
            'report_id' => $context['report_id'] ?? null,
            'report_name' => $context['report_name'] ?? 'unknown',
            'external_id' => $row['id'] ?? $row['Id'] ?? null,
            'payload' => $row,
            'date_init' => $context['date_init'] ?? null,
            'date_end' => $context['date_end'] ?? null,
            'fetched_at' => now(),
        ];
    }
}
