<?php

namespace App\Services\Thinkion\Reports;

use App\Enums\ReportType;
use App\Services\Thinkion\Mappers\GenericReportMapper;
use App\Services\Thinkion\Mappers\VentasReportMapper;
use App\Repositories\Raw\RawReportRepository;
use App\Repositories\Domain\VentaRepository;
use App\Repositories\Domain\ArticuloVentaRepository;
use App\Repositories\Domain\ProductMixRepository;
use App\Services\Thinkion\Mappers\ArticuloVentaReportMapper;
use App\Services\Thinkion\Mappers\ProductMixReportMapper;

class ReportRegistry
{
    /** @var ReportDefinition[] */
    protected array $reports = [];

    public function __construct()
    {
        $this->registerReports();
    }

    /**
     * Get a report definition by its ID.
     *
     * @throws \InvalidArgumentException
     */
    public function getReport(int $reportId): ReportDefinition
    {
        if (!isset($this->reports[$reportId])) {
            throw new \InvalidArgumentException("Report [{$reportId}] is not registered. Register it in ReportRegistry.");
        }

        return $this->reports[$reportId];
    }

    /**
     * Check if a report is registered.
     */
    public function has(int $reportId): bool
    {
        return isset($this->reports[$reportId]);
    }

    /**
     * Get all registered reports.
     *
     * @return ReportDefinition[]
     */
    public function all(): array
    {
        return $this->reports;
    }

    /**
     * Filter reports by type.
     *
     * @return ReportDefinition[]
     */
    public function getByType(ReportType $type): array
    {
        return array_filter($this->reports, fn(ReportDefinition $r) => $r->type === $type);
    }

    /**
     * Get a report or fall back to the generic handler.
     */
    public function getReportOrGeneric(int $reportId): ReportDefinition
    {
        if ($this->has($reportId)) {
            return $this->getReport($reportId);
        }

        // Return a dynamic generic definition for unregistered reports
        return new ReportDefinition(
            reportId: $reportId,
            name: "generic_report_{$reportId}",
            description: "Auto-generated generic handler for report {$reportId}",
            type: ReportType::GENERIC,
            mapperClass: GenericReportMapper::class,
            repositoryClass: RawReportRepository::class,
        );
    }

    /**
     * Register all known reports.
     *
     * Para agregar un nuevo reporte:
     * 1. Crear un mapper que implemente ReportMapperInterface
     * 2. Crear un repository (o reutilizar uno existente)
     * 3. Agregar la definición aquí
     */
    private function registerReports(): void
    {
        // ─────────────────────────────────────────────
        // Reporte 233 — Transacciones → tabla ventas
        // Este es el reporte de ejemplo. Cuando se conozcan los IDs reales
        // de cada reporte, se pueden agregar más entradas aquí.
        // ─────────────────────────────────────────────
        $this->add(new ReportDefinition(
            reportId: 1,
            name: 'transacciones_ventas_legacy',
            description: 'Reporte de transacciones ReportId 1 para tem9',
            type: ReportType::TRANSACTION,
            mapperClass: VentasReportMapper::class,
            repositoryClass: VentaRepository::class,
        ));

        $this->add(new ReportDefinition(
            reportId: 233,
            name: 'transacciones_ventas',
            description: 'Reporte de transacciones que se mapea a la tabla ventas',
            type: ReportType::TRANSACTION,
            mapperClass: VentasReportMapper::class,
            repositoryClass: VentaRepository::class,
        ));

        // ─────────────────────────────────────────────
        // Reportes registrados para Staging (Auditoría SQL)
        // Estos reportes usan el mapeador genérico y se guardan en crudo.
        // ─────────────────────────────────────────────
        $this->add(new ReportDefinition(
            reportId: 234,
            name: 'sales_details',
            description: 'Detalles y auditoría de ventas (MySQL + Audit)',
            type: ReportType::SALES,
            mapperClass: ProductMixReportMapper::class,
            repositoryClass: ProductMixRepository::class,
        ));

        $this->add(new ReportDefinition(
            reportId: 235,
            name: 'products_catalog',
            description: 'Catálogo de productos y existencias (MySQL + Audit)',
            type: ReportType::PRODUCTS,
            mapperClass: ArticuloVentaReportMapper::class,
            repositoryClass: ArticuloVentaRepository::class,
        ));
    }

    private function add(ReportDefinition $report): void
    {
        $this->reports[$report->reportId] = $report;
    }
}
