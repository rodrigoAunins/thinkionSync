<?php

namespace App\Repositories\Domain;

use App\Models\ArticuloVenta;
use App\Repositories\Contracts\DomainRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class ArticuloVentaRepository implements DomainRepositoryInterface
{
    public function upsert(array $data): Model
    {
        // Usamos el ID de Thinkion o un código como match key si lo tenemos.
        // Como 'articulo_ventas' tiene 'descripcion', usaremos eso como match key de ejemplo
        // o el 'id' si viene de la API.
        $match = isset($data['id']) ? ['id' => $data['id']] : ['descripcion' => $data['descripcion']];
        
        return ArticuloVenta::updateOrCreate($match, $data);
    }
}
