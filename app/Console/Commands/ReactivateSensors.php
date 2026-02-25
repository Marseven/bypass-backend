<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ReactivateSensors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:reactivate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer une notification aux approbateurs pour les capteurs à réactiver après la durée d\'inactivité';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Récupérer les requêtes approuvées dont la période d'inactivité est terminée
        $expiredRequests = Request::where('status', 'approved')
            ->where('end_time', '<', $now)
            ->whereHas('sensor', function($query) {
                $query->where('status', 'bypassed');
            })
            ->with(['sensor', 'requester', 'equipment'])
            ->get();

        foreach ($expiredRequests as $request) {
            $sensor = $request->sensor;
            
            if ($sensor) {
                // Préparer le message pour les approbateurs
                $message = "⚠️ *Alerte : Réactivation de Capteur & Son Equipement associe Requise*\n" .
                          "📝 Requête : {$this->getReasonLabel($request->title)}\n" .
                          "👤 Demandeur : {$request->requester->full_name}\n" .
                          "🔧 Équipement : {$request->equipment->name}\n" .
                          "📡 Capteur : {$sensor->name} (ID: {$sensor->id})\n" .
                          "📅 Date de fin prévue : " . $request->end_time->format('d/m/Y H:i') . "\n" .
                          "🔍 Statut actuel : Inactif\n" .
                          "⏰ Action requise : Veuillez vérifier et réactiver le capteur si nécessaire.";

                // Envoyer le message aux administrateurs et superviseurs
                $approvers = User::whereIn('role', ['administrator', 'supervisor'])->get();
                
                foreach ($approvers as $approver) {
                    if ($approver->phone) {
                        $this->sendWhatsAppMessage(ltrim($approver->phone, '+'), $message);
                    }
                }

                Log::info("Notification envoyée aux approbateurs pour le capteur ID: {$sensor->id}, requête ID: {$request->id}");
                $this->info("Notification envoyée pour le capteur ID: {$sensor->id}");
            }
        }

        $this->info('Vérification des capteurs terminée.');
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
                Log::info('Message envoyé avec succès', [
                    'to' => $to,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Erreur envoi message', [
                    'to' => $to,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Exception envoi message', [
                'error' => $e->getMessage(),
                'to' => $to
            ]);
        }
    }
}
