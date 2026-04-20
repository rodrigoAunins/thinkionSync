<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMix extends Model
{
    /**
     * Esta tabla vive en la base de datos de Negocio (Domain).
     */
    protected $connection = 'mysql';

    protected $table = 'vinson_product_mix';
    protected $guarded = [];
    
    // Desactivamos timestamps si la tabla legacy usa nombres distintos o no los usa,
    // pero según mi inspección tiene created_at/updated_at.
    public $timestamps = true;
}
