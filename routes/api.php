<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OraController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

// Public (unversioned)
Route::get('/health', HealthController::class);

Route::get('/', function () {
    return response()->json([
        'name' => 'ByPass API',
        'version' => '1.0.0',
        'endpoints' => ['v1' => '/api/v1'],
    ]);
});

// ──── API v1 ────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Public settings (no auth required)
    Route::get('/settings/public', [SystemController::class, 'getPublicSettings']);

    // Auth (public)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // 2FA verify-login (accessible with temp token, rate-limited)
    Route::post('/auth/2fa/verify-login', [TwoFactorController::class, 'verifyLogin'])
        ->middleware(['auth:sanctum', 'throttle:5,1']);

    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // 2FA management
        Route::post('/auth/2fa/setup', [TwoFactorController::class, 'setup']);
        Route::post('/auth/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('/auth/2fa/disable', [TwoFactorController::class, 'disable']);

        Route::get('/notifications', function () {
            return auth()->user()->notifications;
        });

        // Notification Preferences
        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
        Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);

        // Dashboard
        Route::middleware('permission:dashboard.view')->group(function () {
            Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
            Route::get('/dashboard/recent-requests', [DashboardController::class, 'recentRequests']);
            Route::get('/dashboard/system-status', [DashboardController::class, 'systemStatus']);
            Route::get('/dashboard/request-statistics', [DashboardController::class, 'requestStatistics']);
            Route::get('/dashboard/top-sensors', [DashboardController::class, 'topSensors']);
        });

        // Requests
        Route::middleware('permission:requests.view.own|requests.view.all')->group(function () {
            Route::get('/requests', [RequestController::class, 'index']);
            Route::get('/requests/mine', [RequestController::class, 'mine']);
        });

        Route::middleware('permission:requests.validate.level1|requests.validate.level2')->group(function () {
            Route::get('/requests/pending', [RequestController::class, 'pending']);
            Route::get('/requests/active', [RequestController::class, 'validate_list']);
        });

        Route::middleware('permission:requests.create')->group(function () {
            Route::post('/requests', [RequestController::class, 'store']);
        });

        Route::middleware('permission:requests.view.own|requests.view.all')->group(function () {
            Route::get('/requests/{request}', [RequestController::class, 'show']);
        });

        Route::middleware('permission:requests.validate.level1|requests.validate.level2')->group(function () {
            Route::put('/requests/{request}/validate', [RequestController::class, 'validate']);
        });

        // CDC: new request lifecycle endpoints
        Route::middleware('permission:requests.create|requests.update.own')->group(function () {
            Route::put('/requests/{request}/submit', [RequestController::class, 'submit']);
        });

        Route::middleware('permission:requests.validate.level1|requests.validate.level2')->group(function () {
            Route::put('/requests/{request}/activate', [RequestController::class, 'activate']);
            Route::put('/requests/{request}/close', [RequestController::class, 'close']);
        });

        Route::middleware('permission:requests.update.own|requests.view.all')->group(function () {
            Route::put('/requests/{request}', [RequestController::class, 'update']);
        });

        Route::middleware('permission:requests.delete.own|requests.view.all')->group(function () {
            Route::delete('/requests/{request}', [RequestController::class, 'destroy']);
        });

        Route::get('/notifications/{id}/mark-as-read', [RequestController::class, 'markAsRead'])->name('notifications.mark.as.read');

        // ORA (CDC)
        Route::post('/requests/{request}/ora', [OraController::class, 'store']);
        Route::get('/requests/{request}/ora', [OraController::class, 'show']);
        Route::put('/oras/{ora}/validate', [OraController::class, 'validate']);

        // Sites (CDC)
        Route::middleware('permission:zones.view')->group(function () {
            Route::get('/sites', [SiteController::class, 'index']);
            Route::get('/sites/{site}', [SiteController::class, 'show']);
        });

        Route::middleware('permission:zones.create|zones.update|zones.delete')->group(function () {
            Route::post('/sites', [SiteController::class, 'store']);
            Route::put('/sites/{site}', [SiteController::class, 'update']);
            Route::delete('/sites/{site}', [SiteController::class, 'destroy']);
        });

        // Zones
        Route::middleware('permission:zones.view')->group(function () {
            Route::get('/zones', [ZoneController::class, 'index']);
            Route::get('/zones/{zone}', [ZoneController::class, 'show']);
        });

        Route::middleware('permission:zones.create|zones.update|zones.delete')->group(function () {
            Route::post('/zones', [ZoneController::class, 'store']);
            Route::put('/zones/{zone}', [ZoneController::class, 'update']);
            Route::delete('/zones/{zone}', [ZoneController::class, 'destroy']);
        });

        // Equipment
        Route::middleware('permission:equipment.view')->group(function () {
            Route::get('/equipment', [EquipmentController::class, 'index']);
            Route::get('/equipment/{equipment}', [EquipmentController::class, 'show']);
            Route::get('/zones/{zone}/equipements', [EquipmentController::class, 'index_equipements']);
        });

        Route::middleware('permission:equipment.create|equipment.update|equipment.delete')->group(function () {
            Route::post('/equipment', [EquipmentController::class, 'store']);
            Route::put('/equipment/{equipment}', [EquipmentController::class, 'update']);
            Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy']);
        });

        // Sensors
        Route::middleware('permission:sensors.view')->group(function () {
            Route::get('/equipment/{equipment}/sensors', [SensorController::class, 'index']);
            Route::get('/sensors/{sensor}', [SensorController::class, 'show']);
            Route::get('/sensors', [SensorController::class, 'showSensor']);
        });

        Route::middleware('permission:sensors.create|sensors.update|sensors.delete')->group(function () {
            Route::post('/equipment/{equipment}/sensors', [SensorController::class, 'store']);
            Route::put('/sensors/{sensor}', [SensorController::class, 'update']);
            Route::delete('/sensors/{sensor}', [SensorController::class, 'destroy']);
        });

        // Users
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users/{user}', [UserController::class, 'show']);
        });

        Route::middleware('permission:users.view|users.create|users.update|users.delete')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });

        // Roles & Permissions
        Route::middleware('role_or_permission:administrator|administrateur|users.view')->group(function () {
            Route::get('/roles', [RolePermissionController::class, 'index']);
            Route::get('/permissions', [RolePermissionController::class, 'permissions']);
            Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'updatePermissions']);
        });

        // System & Admin
        Route::middleware('permission:system.settings.manage')->group(function () {
            Route::get('/admin/settings', [SystemController::class, 'getSettings']);
            Route::put('/admin/settings', [SystemController::class, 'updateSettings']);
        });

        Route::middleware('permission:history.view')->group(function () {
            Route::get('/history', [SystemController::class, 'getHistory']);
        });

        // CSV Import
        Route::prefix('import')->group(function () {
            Route::get('/info/{type}', [CsvImportController::class, 'getImportInfo']);
            Route::get('/template/{type}', [CsvImportController::class, 'downloadTemplate']);

            Route::middleware('permission:zones.create')->group(function () {
                Route::post('/zones', [CsvImportController::class, 'importZones']);
            });

            Route::middleware('permission:equipment.create')->group(function () {
                Route::post('/equipment', [CsvImportController::class, 'importEquipment']);
            });

            Route::middleware('permission:sensors.create')->group(function () {
                Route::post('/sensors', [CsvImportController::class, 'importSensors']);
            });
        });
    });
});
