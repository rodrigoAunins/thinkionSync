<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tablas propias del ETL (sync tracking + raw staging).
     * La tabla `ventas` NO se toca — ya existe en producción.
     */
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // Tabla: thinkion_sync_runs — tracking de ejecuciones
        // ─────────────────────────────────────────────
        Schema::create('thinkion_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->integer('report_id')->index();
            $table->string('report_name');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('context_json')->nullable();
            $table->json('totals_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'status']);
            $table->index('started_at');
        });

        // ─────────────────────────────────────────────
        // Tabla: thinkion_raw_reports — datos crudos (staging)
        // ─────────────────────────────────────────────
        Schema::create('thinkion_raw_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('report_id')->index();
            $table->string('report_name')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->json('payload');
            $table->date('date_init')->nullable();
            $table->date('date_end')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thinkion_raw_reports');
        Schema::dropIfExists('thinkion_sync_runs');
    }
};
