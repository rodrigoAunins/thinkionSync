<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticuloVenta extends Model
{
    /**
     * Esta tabla vive en MySQL (Business Domain).
     */
    protected $connection = 'mysql';
    
    protected $table = 'articulo_ventas';
    protected $guarded = [];
}
