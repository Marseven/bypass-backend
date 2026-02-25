<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
use App\Models\Request;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: "/dashboard/summary",
        summary: "Résumé du tableau de bord",
        description: "Retourne un résumé des statistiques principales du système",
        tags: ["Dashboard"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Résumé du tableau de bord",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "new_requests", type: "integer", example: 5, description: "Nouvelles demandes"),
                            new OA\Property(property: "active_requests", type: "integer", example: 12, description: "Demandes actives"),
                            new OA\Property(property: "pending_validation", type: "integer", example: 3, description: "Demandes en attente de validation"),
                            new OA\Property(property: "approved_today", type: "integer", example: 2, description: "Demandes approuvées aujourd'hui"),
                            new OA\Property(property: "connected_users", type: "integer", example: 15, description: "Utilisateurs connectés"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function summary(): JsonResponse
    {
        $data = Cache::remember('dashboard:summary', 300, function () {
            return [
                'new_requests' => Request::pending()->count(),
                'active_requests' => Request::active()->count(),
                'pending_validation' => Request::pending()->count(),
                'approved_today' => Request::approvedToday()->count(),
                'connected_users' => User::where('is_active', true)->count(),
            ];
        });

        return response()->json($data);
    }

    #[OA\Get(
        path: "/dashboard/recent-requests",
        summary: "Dernières demandes",
        description: "Retourne les 10 dernières demandes créées",
        tags: ["Dashboard"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des dernières demandes",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Request")
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function recentRequests(): JsonResponse
    {
        $requests = Cache::remember('dashboard:recent_requests', 120, function () {
            return Request::with(['requester', 'equipment.zone', 'sensor', 'validator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });

        return response()->json($requests);
    }

    #[OA\Get(
        path: "/dashboard/system-status",
        summary: "Statut du système",
        description: "Retourne le statut général du système (équipements, capteurs, alertes)",
        tags: ["Dashboard"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Statut du système",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "monitored_equipment", type: "integer", example: 45, description: "Équipements surveillés"),
                            new OA\Property(property: "online_sensors", type: "integer", example: 120, description: "Capteurs en ligne"),
                            new OA\Property(property: "active_alerts", type: "integer", example: 3, description: "Alertes actives"),
                            new OA\Property(property: "system_performance", type: "number", format: "float", example: 95.5, description: "Performance du système (%)"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function systemStatus(): JsonResponse
    {
        $data = Cache::remember('dashboard:system_status', 300, function () {
            $totalEquipment = Equipment::count();
            $activeEquipment = Equipment::active()->count();

            $totalSensors = Sensor::count();
            $onlineSensors = Sensor::online()->count();

            $totalAlerts = Request::count();
            $activeAlerts = Request::where('status', 'approved')->count();

            $equipmentPerformance = $totalEquipment > 0 ? ($activeEquipment / $totalEquipment) * 100 : 0;
            $sensorPerformance = $totalSensors > 0 ? ($onlineSensors / $totalSensors) * 100 : 0;
            $alertPerformance = $totalAlerts > 0 ? ((1 - ($activeAlerts / $totalAlerts)) * 100) : 100;

            $systemPerformance = ($equipmentPerformance * 0.4) + ($sensorPerformance * 0.4) + ($alertPerformance * 0.2);

            return [
                'monitored_equipment' => $activeEquipment,
                'online_sensors' => $onlineSensors,
                'active_alerts' => $activeAlerts,
                'system_performance' => round($systemPerformance, 2),
            ];
        });

        return response()->json($data);
    }

    public function requestStatistics(HttpRequest $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $cacheKey = "dashboard:request_statistics:{$days}";

        $statistics = Cache::remember($cacheKey, 600, function () use ($days) {
            $startDate = \Carbon\Carbon::now()->subDays($days)->startOfDay();
            $endDate = \Carbon\Carbon::now()->endOfDay();

            // SQL GROUP BY instead of loading all records into PHP
            $grouped = Request::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
                    DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->pluck(null, 'date')
                ->keyBy('date');

            // Fill all dates in the period
            $statistics = [];
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $date = $currentDate->format('Y-m-d');
                $row = $grouped->get($date);
                $statistics[] = [
                    'date' => $date,
                    'total' => $row ? (int) $row->total : 0,
                    'approved' => $row ? (int) $row->approved : 0,
                    'rejected' => $row ? (int) $row->rejected : 0,
                ];
                $currentDate->addDay();
            }

            return $statistics;
        });

        return response()->json($statistics);
    }

    public function topSensors(): JsonResponse
    {
        $result = Cache::remember('dashboard:top_sensors', 600, function () {
            $sensorIds = Request::select('sensor_id', DB::raw('COUNT(*) as request_count'))
                ->whereNotNull('sensor_id')
                ->groupBy('sensor_id')
                ->orderBy('request_count', 'desc')
                ->limit(10)
                ->get();

            // Eager load all sensors in one query instead of N+1
            $sensors = Sensor::with('equipment')
                ->whereIn('id', $sensorIds->pluck('sensor_id'))
                ->get()
                ->keyBy('id');

            $countMap = $sensorIds->pluck('request_count', 'sensor_id');

            return $sensors->map(function (Sensor $sensor) use ($countMap) {
                return [
                    'sensor_id' => $sensor->id,
                    'sensor_name' => $sensor->name,
                    'sensor_code' => $sensor->code,
                    'equipment_name' => $sensor->equipment?->name ?? 'N/A',
                    'request_count' => $countMap[$sensor->id] ?? 0,
                ];
            })->sortByDesc('request_count')->values()->all();
        });

        return response()->json($result);
    }
}
