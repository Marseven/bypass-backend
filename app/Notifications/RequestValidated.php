<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class RequestValidated extends Notification
{
    use Queueable;

    protected $request;
    protected $status;
    protected $rejectionReason;
    protected $validationLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(Request $request, string $status, ?string $rejectionReason = null, ?int $validationLevel = null)
    {
        $this->request = $request;
        $this->status = $status;
        $this->rejectionReason = $rejectionReason;
        $this->validationLevel = $validationLevel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Toujours envoyer en base de données et broadcast
        // Mail seulement si configuré (gestion d'erreur dans le contrôleur)
        $channels = ['database', 'broadcast'];
        
        // Ajouter mail seulement si l'email est configuré
        if (config('mail.mailers.smtp.host')) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $statusLabel = $this->status === 'approved' ? 'Approuvée' : 'Rejetée';
        $subject = "Demande {$statusLabel}";
        
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.requests.validated', [
                'request' => $this->request,
                'status' => $this->status,
                'rejectionReason' => $this->rejectionReason,
                'validationLevel' => $this->validationLevel,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable): array
    {
        $this->request->load(['requester', 'validatorLevel1', 'validatorLevel2', 'equipment.zone', 'sensor']);
        
        $statusLabel = $this->status === 'approved' ? 'Approuvée' : 'Rejetée';
        $description = "Votre demande {$this->request->request_code} a été {$statusLabel}";
        
        if ($this->status === 'rejected' && $this->rejectionReason) {
            $description .= ". Raison : {$this->rejectionReason}";
        }
        
        if ($this->validationLevel) {
            $description .= " (Validation niveau {$this->validationLevel})";
        }
        
        return [
            'id' => $this->request->id,
            'request_id' => $this->request->id,
            'request_code' => $this->request->request_code,
            'title' => $this->request->title,
            'description' => $description,
            'status' => $this->status,
            'status_label' => $statusLabel,
            'priority' => $this->request->priority,
            'rejection_reason' => $this->rejectionReason,
            'validation_level' => $this->validationLevel,
            'requester_name' => $this->request->requester->full_name ?? 'N/A',
            'equipment_name' => $this->request->equipment->name ?? 'N/A',
            'sensor_name' => $this->request->sensor->name ?? 'N/A',
            'url' => url('/requests/mine'),
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        $this->request->load(['requester', 'validatorLevel1', 'validatorLevel2', 'equipment.zone', 'sensor']);
        
        $statusLabel = $this->status === 'approved' ? 'Approuvée' : 'Rejetée';
        $description = "Votre demande {$this->request->request_code} a été {$statusLabel}";
        
        if ($this->status === 'rejected' && $this->rejectionReason) {
            $description .= ". Raison : {$this->rejectionReason}";
        }
        
        if ($this->validationLevel) {
            $description .= " (Validation niveau {$this->validationLevel})";
        }
        
        return new BroadcastMessage([
            'type' => 'request.validated',
            'id' => $this->request->id,
            'request_id' => $this->request->id,
            'request_code' => $this->request->request_code,
            'title' => $this->request->title,
            'description' => $description,
            'status' => $this->status,
            'status_label' => $statusLabel,
            'priority' => $this->request->priority,
            'rejection_reason' => $this->rejectionReason,
            'validation_level' => $this->validationLevel,
            'requester_name' => $this->request->requester->full_name ?? 'N/A',
            'equipment_name' => $this->request->equipment->name ?? 'N/A',
            'sensor_name' => $this->request->sensor->name ?? 'N/A',
            'url' => url('/requests/mine'),
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}

