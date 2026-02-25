<?php

namespace App\Console\Commands;

use App\Enums\EquipmentStatus;
use App\Enums\RequestReason;
use App\Enums\RequestStatus;
use App\Enums\SensorStatus;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Request;
use App\Models\User;
use App\Models\Zone;
use App\Notifications\RequestValidated;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessRequests extends Command
{
    protected $signature = 'app:process-requests';

    protected $description = 'Process active bypasses (expire overdue, warn expiring, check zone thresholds), send pending reminders';

    public function handle(): void
    {
        $this->sendExpirationWarnings();
        $this->expireActiveBypassesOverdue();
        $this->sendPendingReminders();
        $this->cancelExpiredPendingRequests();
        $this->checkZoneBypassThresholds();

        $this->info('Traitement des requêtes terminé.');
    }

    /**
     * Warn about bypasses expiring within 4 hours.
     */
    private function sendExpirationWarnings(): void
    {
        $soonExpiring = Request::where('status', RequestStatus::Active->value)
            ->whereBetween('end_time', [now(), now()->addHours(4)])
            ->whereNull('expiration_warned_at')
            ->with(['requester', 'sensor', 'equipment'])
            ->get();

        foreach ($soonExpiring as $request) {
            $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;
            $hoursLeft = now()->diffInMinutes($request->end_time);
            $timeLabel = $hoursLeft >= 60
                ? round($hoursLeft / 60, 1) . 'h'
                : $hoursLeft . ' min';

            $message = "⏰ *Alerte : Bypass expire bientôt*\n" .
                "📝 Code : {$request->request_code}\n" .
                "📝 Titre : {$reasonLabel}\n" .
                "📅 Expiration : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                "⏳ Temps restant : {$timeLabel}\n" .
                "🔍 Prenez les dispositions nécessaires.";

            // Notify requester
            if ($request->requester?->phone) {
                $phone = ltrim($request->requester->phone, '+');
                SendWhatsAppNotification::dispatch($phone, $message);
            }

            // Notify validators (chef de quart)
            $this->dispatchToValidators($message);

            $request->update(['expiration_warned_at' => now()]);
        }

        if ($soonExpiring->count() > 0) {
            $this->info("Sent expiration warnings for {$soonExpiring->count()} bypass(es).");
        }
    }

    /**
     * CDC: active bypasses past end_time → expired, restore sensor/equipment
     */
    private function expireActiveBypassesOverdue(): void
    {
        $expiredActive = Request::where('status', RequestStatus::Active->value)
            ->where('end_time', '<', now())
            ->with(['requester', 'sensor', 'equipment'])
            ->get();

        foreach ($expiredActive as $request) {
            $request->update(['status' => RequestStatus::Expired->value]);

            // Restore sensor and equipment
            if ($request->sensor) {
                $request->sensor->update(['status' => SensorStatus::Active->value]);
            }
            if ($request->equipment) {
                $request->equipment->update(['status' => EquipmentStatus::Operational->value]);
            }

            $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;

            $message = "⚠️ *Bypass Expiré*\n" .
                "📝 Code : {$request->request_code}\n" .
                "📝 Titre : {$reasonLabel}\n" .
                "📅 Date fin : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                "🔍 Le bypass a été automatiquement expiré et le capteur restauré.";

            // Notify requester
            if ($request->requester?->phone) {
                $phone = ltrim($request->requester->phone, '+');
                SendWhatsAppNotification::dispatch($phone, $message);
            }

            // Notify validators
            $this->dispatchToValidators($message);

            // Notify hierarchy (HSE + directors) via WhatsApp
            $this->dispatchToHierarchy($message);

            // Laravel database notification to requester
            if ($request->requester) {
                try {
                    $request->requester->notify(new RequestValidated($request, 'expired', null, null));
                } catch (\Exception $e) {
                    Log::warning('Failed to send expiry notification', ['error' => $e->getMessage()]);
                }
            }
        }

        if ($expiredActive->count() > 0) {
            $this->info("Expired {$expiredActive->count()} active bypass(es).");
        }
    }

    private function sendPendingReminders(): void
    {
        $pendingRequests = Request::where('status', RequestStatus::Pending->value)
            ->where('end_time', '>', now())
            ->with('requester')
            ->get();

        foreach ($pendingRequests as $request) {
            $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;

            $message = "📌 *Rappel : Demande en attente*\n" .
                "👤 Demandeur : {$request->requester->full_name}\n" .
                "📝 Titre : {$reasonLabel}\n" .
                "⚡ Priorité : {$request->priority}\n" .
                "📅 Date limite : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                "🔍 Statut : En attente de validation.\n" .
                "Merci de traiter cette demande dès que possible.";

            $this->dispatchToValidators($message);
        }
    }

    private function cancelExpiredPendingRequests(): void
    {
        $expiredRequests = Request::where('status', RequestStatus::Pending->value)
            ->where('end_time', '<', now())
            ->with('requester')
            ->get();

        foreach ($expiredRequests as $request) {
            $request->update(['status' => RequestStatus::Expired->value]);

            $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;

            $message = "📌 *Notification : Demande expirée*\n" .
                "📝 Titre : {$reasonLabel}\n" .
                "⚡ Priorité : {$request->priority}\n" .
                "📅 Date limite : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                "🔍 Statut : Expirée automatiquement car la date limite a été dépassée.";

            $this->dispatchToValidators($message);
        }
    }

    /**
     * Check if any zone has >= 30% of sensors in bypass.
     */
    private function checkZoneBypassThresholds(): void
    {
        $zones = Zone::with('equipements.sensors')->get();

        foreach ($zones as $zone) {
            $totalSensors = 0;
            $bypassedSensors = 0;

            foreach ($zone->equipements as $equipment) {
                foreach ($equipment->sensors as $sensor) {
                    $totalSensors++;
                    if ($sensor->status === SensorStatus::Bypassed->value || $sensor->status === 'bypassed') {
                        $bypassedSensors++;
                    }
                }
            }

            if ($totalSensors === 0) {
                continue;
            }

            $ratio = $bypassedSensors / $totalSensors;

            if ($ratio >= 0.3) {
                $cacheKey = "zone_threshold_warned:{$zone->id}";

                if (Cache::has($cacheKey)) {
                    continue;
                }

                $percent = round($ratio * 100);
                $message = "🚨 *Alerte Seuil Zone*\n" .
                    "📍 Zone : {$zone->name}\n" .
                    "📊 {$bypassedSensors}/{$totalSensors} capteurs en bypass ({$percent}%)\n" .
                    "⚠️ Le seuil de 30% est dépassé.\n" .
                    "🔍 Action requise : vérifier la situation.";

                // Notify resp_exploitation and hierarchy
                $this->dispatchToHierarchy($message);
                $this->dispatchToValidators($message);

                Cache::put($cacheKey, true, now()->addHours(24));

                $this->warn("Zone '{$zone->name}' has {$percent}% sensors bypassed.");
            }
        }
    }

    private function dispatchToValidators(string $message): void
    {
        $validators = User::whereIn('role', [
                User::ROLE_CHEF_DE_QUART, User::ROLE_RESP_EXPLOITATION,
                User::ROLE_ADMINISTRATEUR,
                'supervisor', 'administrator', // legacy
            ])
            ->whereNotNull('phone')
            ->get();

        foreach ($validators as $user) {
            $phone = ltrim($user->phone, '+');
            SendWhatsAppNotification::dispatch($phone, $message);
        }
    }

    private function dispatchToHierarchy(string $message): void
    {
        $hierarchy = User::whereIn('role', [
                User::ROLE_RESPONSABLE_HSE,
                User::ROLE_DIRECTEUR,
                'director', // legacy
            ])
            ->whereNotNull('phone')
            ->get();

        foreach ($hierarchy as $user) {
            $phone = ltrim($user->phone, '+');
            SendWhatsAppNotification::dispatch($phone, $message);
        }
    }
}
