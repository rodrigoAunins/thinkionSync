<?php

namespace App\Services\Thinkion\Reports;

use App\Enums\ReportType;

class ReportDefinition
{
    public function __construct(
        public int $reportId,
        public string $name,
        public string $description,
        public ReportType $type,
        public string $mapperClass,
        public string $repositoryClass,
    ) {
    }
}
