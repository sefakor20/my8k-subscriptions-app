<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService,
    ) {}

    /**
     * Simple ping endpoint for uptime monitors.
     * Returns a basic status response.
     */
    public function ping(): JsonResponse
    {
        return response()->json(
            $this->healthCheckService->ping(),
        );
    }

    /**
     * Full health check endpoint with detailed system status.
     * Includes database, cache, queue, and storage checks.
     */
    public function check(): JsonResponse
    {
        $health = $this->healthCheckService->check();

        $statusCode = match ($health['status']) {
            'ok' => 200,
            'degraded' => 200,
            'down' => 503,
            default => 200,
        };

        return response()->json($health, $statusCode);
    }

    /**
     * Extended health check including provisioning metrics.
     * Only accessible to authenticated admins.
     */
    public function detailed(): JsonResponse
    {
        $health = $this->healthCheckService->check();
        $health['checks']['provisioning'] = $this->healthCheckService->checkProvisioningHealth();

        $statusCode = match ($health['status']) {
            'ok' => 200,
            'degraded' => 200,
            'down' => 503,
            default => 200,
        };

        return response()->json($health, $statusCode);
    }
}
