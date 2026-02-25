<?php
// app/Services/WhapiService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhapiService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.whapi.base_url');
        $this->token = config('services.whapi.token');
    }

    /**
     * Envoie un message texte WhatsApp
     *
     * @param string $to Numéro de téléphone (ex: "33612345678")
     * @param string $body Le message à envoyer
     * @return array
     * @throws Exception
     */
    public function sendTextMessage($to, $body)
    {
        if (empty($this->token)) {
            throw new Exception('Token Whapi non configuré');
        }

        $endpoint = $this->baseUrl . '/messages/text';
        
        $data = [
            'to' => $to,
            'body' => $body
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($endpoint, $data);

            if ($response->successful()) {
                Log::info('Message WhatsApp envoyé', [
                    'to' => $to,
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Message envoyé avec succès'
                ];
            } else {
                Log::error('Erreur envoi WhatsApp', [
                    'to' => $to,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => false,
                    'error' => $response->json(),
                    'message' => 'Erreur lors de l\'envoi'
                ];
            }
        } catch (Exception $e) {
            Log::error('Exception envoi WhatsApp', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Erreur de connexion à l\'API Whapi: ' . $e->getMessage());
        }
    }

    /**
     * Envoie des messages en lot
     *
     * @param array $messages [['to' => '33612345678', 'body' => 'Message']]
     * @return array
     */
    public function sendBulkMessages($messages)
    {
        $results = [];
        $success = 0;
        $errors = 0;

        foreach ($messages as $message) {
            try {
                $result = $this->sendTextMessage($message['to'], $message['body']);
                $results[] = array_merge($result, ['to' => $message['to']]);
                
                if ($result['success']) {
                    $success++;
                } else {
                    $errors++;
                }
                
                // Pause pour éviter les limites de taux
                sleep(1);
                
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'to' => $message['to'],
                    'error' => $e->getMessage()
                ];
                $errors++;
            }
        }

        return [
            'total' => count($messages),
            'success' => $success,
            'errors' => $errors,
            'results' => $results
        ];
    }

    /**
     * Formate le numéro de téléphone
     *
     * @param string $phone
     * @return string
     */
    public function formatPhoneNumber($phone)
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // // Ajouter l'indicatif pays si nécessaire
        // if (substr($phone, 0, 1) === '0') {
        //     $phone = '33' . substr($phone, 1);
        // }
        
        return $phone;
    }
}