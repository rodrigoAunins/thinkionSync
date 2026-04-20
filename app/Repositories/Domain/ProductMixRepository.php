<?php

namespace App\Repositories\Domain;

use App\Models\ProductMix;
use App\Repositories\Contracts\DomainRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class ProductMixRepository implements DomainRepositoryInterface
{
    public function upsert(array $data): Model
    {
        // El match key lógico para el detalle de productos suele ser establecimiento + fecha + producto
        $match = [
            'store_id'   => $data['store_id'] ?? null,
            'date'       => $data['date'] ?? null,
            'idProducto' => $data['idProducto'] ?? null,
            'codigo'     => $data['codigo'] ?? null,
        ];
        
        return ProductMix::updateOrCreate($match, $data);
    }
}
