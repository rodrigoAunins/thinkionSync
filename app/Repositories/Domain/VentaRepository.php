<?php

namespace App\Repositories\Domain;

use App\Models\Venta;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Services\Thinkion\Support\SyncLogger;
use Illuminate\Database\Eloquent\Model;

class VentaRepository implements DomainRepositoryInterface
{
    /**
     * Upsert a venta record.
     * Match key: nro_venta + fecha_venta (unique business identifier).
     */
    public function upsert(array $data): Model
    {
        $match = [
            'nro_venta' => $data['nro_venta'],
            'fecha_venta' => \Carbon\Carbon::parse($data['fecha_venta'])->startOfDay(),
        ];

        SyncLogger::logInfo("[VENTA_REPO] Attempting upsert", [
            'match' => $match,
        ]);

        try {
            $venta = Venta::updateOrCreate($match, $data);

            SyncLogger::logInfo("[VENTA_REPO] " . ($venta->wasRecentlyCreated ? 'INSERTED' : 'UPDATED'), [
                'id' => $venta->id,
                'nro_venta' => $venta->nro_venta,
            ]);

            return $venta;
        } catch (\Throwable $e) {
            SyncLogger::logError("[VENTA_REPO] Failed upsert", [
                'nro_venta' => $data['nro_venta'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
