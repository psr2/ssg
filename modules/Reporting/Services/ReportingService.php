<?php

namespace Modules\Reporting\Services;

use Modules\Reporting\Repositories\ReportingRepository;

class ReportingService
{
    public function __construct(protected ReportingRepository $repository) {}

    /**
     * Get overall report data overview.
     */
    public function getOverviewReport(): array
    {
        return [
            'status' => 'success',
            'data' => $this->repository->getInventorySummary(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
