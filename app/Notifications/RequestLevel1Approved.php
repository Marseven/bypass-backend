<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class RequestLevel1Approved extends Notification
{
    use Queueable;

    protected $request;

    /**
     * Create a new notification instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
        return (new MailMessage)
            ->subject('Validation Niveau 1 Approuvée - Action Requise')
            ->view('emails.requests.level1_approved', [
                'request' => $this->request,
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
        $this->request->load(['requester', 'validatorLevel1', 'equipment.zone', 'sensor']);
        
        return [
            'id' => $this->request->id,
            'request_id' => $this->request->id,
            'request_code' => $this->request->request_code,
            'title' => $this->request->title,
            'description' => "La demande {$this->request->request_code} a été approuvée au niveau 1. Validation niveau 2 requise.",
            'priority' => $this->request->priority,
            'status' => $this->request->status,
            'requester_name' => $this->request->requester->full_name ?? 'N/A',
            'validator_level1_name' => $this->request->validatorLevel1->full_name ?? 'N/A',
            'equipment_name' => $this->request->equipment->name ?? 'N/A',
            'sensor_name' => $this->request->sensor->name ?? 'N/A',
            'url' => url('/validation'),
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        $this->request->load(['requester', 'validatorLevel1', 'equipment.zone', 'sensor']);
        
        return new BroadcastMessage([
            'type' => 'request.level1_approved',
            'id' => $this->request->id,
            'request_id' => $this->request->id,
            'request_code' => $this->request->request_code,
            'title' => $this->request->title,
            'description' => "La demande {$this->request->request_code} a été approuvée au niveau 1. Validation niveau 2 requise.",
            'priority' => $this->request->priority,
            'status' => $this->request->status,
            'requester_name' => $this->request->requester->full_name ?? 'N/A',
            'validator_level1_name' => $this->request->validatorLevel1->full_name ?? 'N/A',
            'equipment_name' => $this->request->equipment->name ?? 'N/A',
            'sensor_name' => $this->request->sensor->name ?? 'N/A',
            'url' => url('/validation'),
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}

