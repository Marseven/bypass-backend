<?php

namespace App\Services;

use App\Enums\RequestReason;
use App\Enums\Priority;
use App\Enums\ValidationStatus;
use App\Jobs\SendWhatsAppNotification;
use App\Models\NotificationPreference;
use App\Models\Request;
use App\Models\User;
use App\Notifications\RequestCreate;
use App\Notifications\RequestLevel1Approved;
use App\Notifications\RequestUpdate;
use App\Notifications\RequestValidated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService implements \App\Contracts\NotificationServiceInterface
{

    public function notifyRequestCreated(Request $request): void
    {
        $request->load(['requester', 'equipment.zone', 'sensor']);

        $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;
        $priorityLabel = Priority::tryFrom($request->priority)?->label() ?? $request->priority;

        $message = "📌 *Nouvelle Demande Créée*\n" .
            "👤 Demandeur : {$request->requester->full_name}\n" .
            "📝 Titre : {$reasonLabel}\n" .
            "⚡ Priorité : {$priorityLabel}\n" .
            "📅 Soumis le : " . now()->format('d/m/Y H:i') . "\n" .
            "🔍 Statut : En cours de validation.";

        // WhatsApp au demandeur
        $this->sendWhatsAppSafe($request->requester, $message, 'request_created');

        // Notifications Laravel aux validateurs
        $validators = User::permission('requests.validate.level1')->get();
        $this->sendLaravelNotificationSafe($validators, new RequestCreate($request));

        // WhatsApp aux validateurs
        $adminMessage = "📌 *Nouvelle Demande à Valider*\n" .
            "👤 Demandeur : {$request->requester->full_name}\n" .
            "📝 Titre : {$reasonLabel}\n" .
            "⚡ Priorité : {$priorityLabel}\n" .
            "📅 Soumis le : " . now()->format('d/m/Y H:i') . "\n" .
            "🔍 Statut : En attente de votre validation.\n" .
            "📂 Consultez la demande dans le système pour plus de détails.";

        foreach ($validators as $user) {
            $this->sendWhatsAppSafe($user, $adminMessage, 'request_created');
        }
    }

    public function notifyValidationResult(Request $request, string $status, ?string $rejectionReason = null, ?int $validationLevel = null): void
    {
        $request->load(['requester', 'validatorLevel1', 'validatorLevel2', 'equipment.zone', 'sensor']);

        $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;
        $statusLabel = $status === ValidationStatus::Approved->value ? 'Approuvée' : 'Rejetée';

        // Notification Laravel au demandeur
        if ($request->requester) {
            $this->sendLaravelNotificationSafe(
                collect([$request->requester]),
                new RequestValidated($request, $status, $rejectionReason, $validationLevel)
            );
        }

        // WhatsApp au demandeur
        $requesterMessage = "📌 *Notification : Requête {$statusLabel}*\n" .
            "📝 Titre : {$reasonLabel}\n" .
            "⚡ Statut : {$statusLabel}\n" .
            ($status === 'rejected' && $rejectionReason ? "❌ Raison du rejet : {$rejectionReason}\n" : "") .
            "📅 Validée le : " . now()->format('d/m/Y H:i') . "\n";

        $this->sendWhatsAppSafe($request->requester, $requesterMessage, 'validation_result');

        // WhatsApp aux directeurs
        $directorMessage = "📌 *Notification : Requête {$statusLabel}*\n" .
            "👤 Demandeur : {$request->requester->full_name}\n" .
            "📝 Titre : {$reasonLabel}\n" .
            "⚡ Statut : {$statusLabel}\n" .
            ($status === 'rejected' && $rejectionReason ? "❌ Raison du rejet : {$rejectionReason}\n" : "") .
            "📅 Validée le : " . now()->format('d/m/Y H:i') . "\n";

        $directors = User::role('administrator')->get();
        foreach ($directors as $director) {
            $this->sendWhatsAppSafe($director, $directorMessage, 'validation_result');
        }
    }

    public function notifyLevel1Approved(Request $request): void
    {
        $request->load(['requester', 'validatorLevel1', 'equipment.zone', 'sensor']);

        $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;
        $priorityLabel = Priority::tryFrom($request->priority)?->label() ?? $request->priority;

        $administrators = User::permission('requests.validate.level2')->get();
        $this->sendLaravelNotificationSafe($administrators, new RequestLevel1Approved($request));

        $message = "📌 *Validation Niveau 1 Approuvée - Validation Niveau 2 Requise*\n" .
            "👤 Demandeur : {$request->requester->full_name}\n" .
            "📝 Titre : {$reasonLabel}\n" .
            "⚡ Priorité : {$priorityLabel}\n" .
            "✅ Validée niveau 1 par : {$request->validatorLevel1->full_name}\n" .
            "⏳ En attente de votre validation niveau 2.\n" .
            "📂 Consultez la demande dans le système pour plus de détails.";

        foreach ($administrators as $admin) {
            $this->sendWhatsAppSafe($admin, $message, 'validation_result');
        }
    }

    public function notifyRequestUpdated(Request $request): void
    {
        $request->load(['requester', 'equipment.zone', 'sensor']);

        $reasonLabel = RequestReason::tryFrom($request->title)?->label() ?? $request->title;
        $priorityLabel = Priority::tryFrom($request->priority)?->label() ?? $request->priority;

        $validators = User::permission('requests.validate.level1')->get();
        $this->sendLaravelNotificationSafe($validators, new RequestUpdate($request));

        $updateMessage = "📌 *Demande Modifiée*\n" .
            "👤 Demandeur : {$request->requester->full_name}\n" .
            "📝 Code : {$request->request_code}\n" .
            "📝 Titre : {$reasonLabel}\n" .
            "⚡ Priorité : {$priorityLabel}\n" .
            "📅 Modifiée le : " . now()->format('d/m/Y H:i') . "\n" .
            "🔍 Statut : En attente de validation.";

        foreach ($validators as $validator) {
            $this->sendWhatsAppSafe($validator, $updateMessage, 'request_created');
        }
    }

    private function shouldNotify(User $user, string $channel, string $eventType): bool
    {
        return NotificationPreference::isEnabled($user->id, $channel, $eventType);
    }

    private function sendWhatsAppSafe(?User $user, string $message, string $eventType = 'request_created'): void
    {
        if (!$user || !$user->phone) {
            return;
        }

        if (!$this->shouldNotify($user, 'whatsapp', $eventType)) {
            return;
        }

        $phone = ltrim($user->phone, '+');
        SendWhatsAppNotification::dispatch($phone, $message);
    }

    private function sendLaravelNotificationSafe($users, $notification): void
    {
        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, $notification);
        } catch (\Exception $e) {
            Log::warning('Erreur envoi notification batch, tentative individuelle', [
                'error' => $e->getMessage(),
            ]);
            foreach ($users as $user) {
                try {
                    $user->notify($notification);
                } catch (\Exception $e2) {
                    Log::error('Erreur critique notification', [
                        'user_id' => $user->id,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }
    }
}
