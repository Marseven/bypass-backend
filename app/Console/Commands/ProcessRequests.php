<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CreateRequestRequest;
use App\Http\Requests\ValidateRequestRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Notifications\RequestCreate;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;

class ProcessRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer des rappels pour les requêtes en attente et annuler celles dépassées';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // 1. Envoyer des rappels pour les requêtes en attente
        $pendingRequests = Request::where('status', 'pending')
            ->where('end_time', '>', now())
            ->get();

        foreach ($pendingRequests as $request) {
            $responsibleUsers = User::whereIn('role', ['supervisor', 'administrator'])->get();

            foreach ($responsibleUsers as $user) {
                $phone = ltrim($user->phone, '+'); // Supprime le "+" si nécessaire
                $message = "📌 *Rappel : Demande en attente*\n" .
                        "👤 Demandeur : {$request->requester->full_name}\n" .
                        "📝 Titre : {$request->title}\n" .
                        "⚡ Priorité : {$request->priority}\n" .
                        "📅 Date limite : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                        "🔍 Statut : En attente de validation.\n" .
                        "Merci de traiter cette demande dès que possible.";

                $this->sendWhatsAppMessage($phone, $message);
            }
        }

        // 2. Annuler les requêtes dépassées
        $expiredRequests = Request::where('status', 'pending')
            ->where('end_time', '<', now())
            ->get();

        foreach ($expiredRequests as $request) {
            $request->update(['status' => 'cancelled']);

            $responsibleUsers = User::whereIn('role', ['supervisor', 'administrator'])->get();

            foreach ($responsibleUsers as $user) {
                $phone = ltrim($user->phone, '+');
                $message = "📌 *Notification : Demande annulée*\n" .
                        "📝 Titre : {$this->getReasonLabel($request->title)}\n".
                        "⚡ Priorité : {$request->priority}\n" .
                        "📅 Date limite : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                        "🔍 Statut : Annulée automatiquement car la date limite a été dépassée.";

                $this->sendWhatsAppMessage($phone, $message);
            }
        }
        $this->info('Traitement des requêtes terminé.');
    
    }

    private function getReasonLabel(string $key): string
    {
        $reasonLabels = [
            'preventive_maintenance' => 'Maintenance préventive',
            'corrective_maintenance' => 'Maintenance corrective',
            'calibration' => 'Étalonnage',
            'testing' => 'Tests',
            'emergency_repair' => 'Réparation d\'urgence',
            'system_upgrade' => 'Mise à niveau système',
            'investigation' => 'Investigation',
            'other' => 'Autre'
        ];

        return $reasonLabels[$key] ?? $key;
    }

    private function sendWhatsAppMessage($to, $text)
    {
        $baseUrl = config('services.whapi.base_url');
        $token = config('services.whapi.token');
        
        if (!$token) {
            Log::warning('Token Whapi non configuré');
            return;
        }
        
        try {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/messages/text', [
                'to' => $to,
                'body' => $text
            ]);

            if ($response->successful()) {
                Log::info('Message WhatsApp envoyé avec succès');
            } else {
                Log::error('Erreur envoi message WhatsApp', [
                    'status' => $response->status(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exception envoi message WhatsApp', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
