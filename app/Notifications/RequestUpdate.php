<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class RequestUpdate extends Notification
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
            ->subject('Demande Modifiée')
            ->view('emails.requests.updated', [
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
        $this->request->load(['requester', 'equipment.zone', 'sensor']);
        
        return [
            'id' => $this->request->id,
            'request_id' => $this->request->id,
            'request_code' => $this->request->request_code,
            'title' => $this->request->title,
            'description' => $this->request->description ?? '',
            'priority' => $this->request->priority,
            'status' => $this->request->status,
            'requester_name' => $this->request->requester->full_name ?? 'N/A',
            'equipment_name' => $this->request->equipment->name ?? 'N/A',
            'sensor_name' => $this->request->sensor->name ?? 'N/A',
            'url' => url('/validation'),
            'created_at' => $this->request->updated_at->toDateTimeString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        $this->request->load(['requester', 'equipment.zone', 'sensor']);
        
        return new BroadcastMessage([
            'type' => 'request.updated',
            'id' => $this->request->id,
            'request_id' => $this->request->id,
            'request_code' => $this->request->request_code,
            'title' => $this->request->title,
            'description' => $this->request->description ?? '',
            'priority' => $this->request->priority,
            'status' => $this->request->status,
            'requester_name' => $this->request->requester->full_name ?? 'N/A',
            'equipment_name' => $this->request->equipment->name ?? 'N/A',
            'sensor_name' => $this->request->sensor->name ?? 'N/A',
            'url' => url('/validation'),
            'created_at' => $this->request->updated_at->toDateTimeString(),
        ]);
    }
}

