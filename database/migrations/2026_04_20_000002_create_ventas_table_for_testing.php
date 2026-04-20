<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla ventas — estructura legacy.
     *
     * En producción esta tabla ya existe en MySQL.
     * Esta migración existe SOLAMENTE para testing con SQLite.
     * En producción se puede excluir o simplemente no ejecutar.
     */
    public function up(): void
    {
        if (Schema::hasTable('ventas')) {
            return; // No recrear si ya existe
        }

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nro_venta');
            $table->unsignedInteger('indice_venta')->nullable();
            $table->date('fecha_venta');
            $table->unsignedInteger('turno')->nullable();
            $table->unsignedInteger('clima')->nullable();
            $table->unsignedInteger('users_id')->nullable();
            $table->double('ingresos', 11, 2)->nullable();
            $table->double('anulaciones', 11, 2)->nullable();
            $table->double('egresos', 11, 2)->nullable();
            $table->double('ventas_fiscal', 11, 2)->nullable();
            $table->double('ventas_no_fiscal', 11, 2)->nullable();
            $table->double('fp1', 11, 2)->nullable();
            $table->double('fp2', 11, 2)->nullable();
            $table->double('fp3', 11, 2)->nullable();
            $table->double('fp4', 11, 2)->nullable();
            $table->double('fp5', 11, 2)->nullable();
            $table->double('fp6', 11, 2)->nullable();
            $table->double('fp7', 11, 2)->nullable();
            $table->double('fp8', 11, 2)->nullable();
            $table->double('fp9', 11, 2)->nullable();
            $table->double('fp10', 11, 2)->nullable();
            $table->double('venta_alimentos', 11, 2)->nullable();
            $table->double('venta_bebidas', 11, 2)->nullable();
            $table->double('art1', 11, 2)->nullable();
            $table->double('art2', 11, 2)->nullable();
            $table->double('art3', 11, 2)->nullable();
            $table->double('art4', 11, 2)->nullable();
            $table->double('art5', 11, 2)->nullable();
            $table->double('art6', 11, 2)->nullable();
            $table->double('art7', 11, 2)->nullable();
            $table->double('art8', 11, 2)->nullable();
            $table->double('art9', 11, 2)->nullable();
            $table->double('art10', 11, 2)->nullable();
            $table->double('art11', 11, 2)->nullable();
            $table->double('art12', 11, 2)->nullable();
            $table->string('obs', 191)->nullable();
            $table->integer('caja')->nullable();
            $table->unsignedInteger('estado_venta')->nullable();
            $table->timestamps();
            $table->double('derecho_show', 11, 2)->nullable();
            $table->integer('dshowId')->nullable();
            $table->double('cuenta_corriente', 11, 2)->nullable();
            $table->double('indice_pinta', 11, 2)->nullable();
            $table->string('diferenciaCaja', 191)->nullable();
            $table->double('valor_studio_caja_chica', 11, 2)->nullable();
            $table->double('valor_bosque_caja_chica', 11, 2)->nullable();

            $table->index(['nro_venta', 'fecha_venta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
