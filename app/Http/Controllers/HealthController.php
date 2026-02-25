<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $allUp = !in_array('down', $services, true);
        $status = $allUp ? 'healthy' : 'degraded';
        $httpCode = $allUp ? 200 : 503;

        return response()->json([
            'status' => $status,
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toISOString(),
            'services' => $services,
        ], $httpCode);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'up';
        } catch (\Exception) {
            return 'down';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::store()->put('health_check', true, 5);
            return Cache::store()->get('health_check') === true ? 'up' : 'down';
        } catch (\Exception) {
            return 'down';
        }
    }

    private function checkQueue(): string
    {
        try {
            if (Schema::hasTable('jobs')) {
                DB::table('jobs')->count();
                return 'up';
            }
            return 'unknown';
        } catch (\Exception) {
            return 'down';
        }
    }
}
