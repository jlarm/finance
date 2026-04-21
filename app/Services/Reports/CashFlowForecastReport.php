<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Services\CashFlowForecastService;

/**
 * Thin report wrapper over CashFlowForecastService so the reports
 * layer has a single, symmetric shape for controllers to consume.
 * If the underlying forecast evolves, this is where we adapt the
 * payload for report pages without touching forecast internals.
 */
class CashFlowForecastReport
{
    public function __construct(
        private CashFlowForecastService $forecast,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return $this->forecast->forecast($user);
    }
}
