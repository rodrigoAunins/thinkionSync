<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    /**
     * Tabla legacy existente en el entorno productivo.
     * El ETL NO debe recrearla ni alterarla estructuralmente.
     */
    protected $table = 'ventas';

    /**
     * Permitimos asignación masiva para facilitar el mapeo dinámico.
     */
    protected $guarded = [];

    protected $casts = [
        'fecha_venta' => 'date',
        'ingresos' => 'decimal:2',
        'anulaciones' => 'decimal:2',
        'egresos' => 'decimal:2',
        'ventas_fiscal' => 'decimal:2',
        'ventas_no_fiscal' => 'decimal:2',
        'venta_alimentos' => 'decimal:2',
        'venta_bebidas' => 'decimal:2',
        'fp1' => 'decimal:2',
        'fp2' => 'decimal:2',
        'fp3' => 'decimal:2',
        'fp4' => 'decimal:2',
        'fp5' => 'decimal:2',
        'fp6' => 'decimal:2',
        'fp7' => 'decimal:2',
        'fp8' => 'decimal:2',
        'fp9' => 'decimal:2',
        'fp10' => 'decimal:2',
        'derecho_show' => 'decimal:2',
        'cuenta_corriente' => 'decimal:2',
        'indice_pinta' => 'decimal:2',
        'valor_studio_caja_chica' => 'decimal:2',
        'valor_bosque_caja_chica' => 'decimal:2',
    ];

    /**
     * La tabla legacy tiene created_at/updated_at — habilitamos timestamps.
     */
    public $timestamps = true;
}
