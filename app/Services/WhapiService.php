<?php

namespace App\Services;

use App\Contracts\MessagingServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhapiService implements MessagingServiceInterface
{
    protected $baseUrl;
    protected $token;

    private const CIRCUIT_CACHE_KEY = 'whapi_circuit_open';
    private const FAILURES_CACHE_KEY = 'whapi_failures';
    private const FAILURE_THRESHOLD = 5;
    private const CIRCUIT_OPEN_SECONDS = 60;

    public function __construct()
    {
        $this->baseUrl = config('services.whapi.base_url');
        $this->token = config('services.whapi.token');
    }

    public function sendTextMessage($to, $body): array
    {
        if (empty($this->token)) {
            throw new Exception('Token Whapi non configuré');
        }

        if ($this->isCircuitOpen()) {
            Log::warning('WhatsApp circuit breaker is open, skipping message');
            return [
                'success' => false,
                'message' => 'Service temporarily unavailable (circuit breaker open)',
            ];
        }

        $endpoint = $this->baseUrl . '/messages/text';

        $data = [
            'to' => $to,
            'body' => $body,
        ];

        try {
            $response = Http::timeout(10)
                ->retry(3, 200, throw: false)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post($endpoint, $data);

            if ($response->successful()) {
                Log::info('Message WhatsApp envoyé avec succès');
                $this->recordSuccess();

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Message envoyé avec succès',
                ];
            } else {
                Log::error('Erreur envoi WhatsApp', [
                    'status' => $response->status(),
                ]);
                $this->recordFailure();

                return [
                    'success' => false,
                    'error' => $response->json(),
                    'message' => 'Erreur lors de l\'envoi',
                ];
            }
        } catch (Exception $e) {
            Log::error('Exception envoi WhatsApp', [
                'error' => $e->getMessage(),
            ]);
            $this->recordFailure();

            throw new Exception('Erreur de connexion à l\'API Whapi: ' . $e->getMessage());
        }
    }

    public function sendBulkMessages($messages): array
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

                sleep(1);
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'to' => $message['to'],
                    'error' => $e->getMessage(),
                ];
                $errors++;
            }
        }

        return [
            'total' => count($messages),
            'success' => $success,
            'errors' => $errors,
            'results' => $results,
        ];
    }

    public function formatPhoneNumber($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $phone;
    }

    private function isCircuitOpen(): bool
    {
        return Cache::get(self::CIRCUIT_CACHE_KEY, false);
    }

    private function recordSuccess(): void
    {
        Cache::forget(self::FAILURES_CACHE_KEY);
    }

    private function recordFailure(): void
    {
        $failures = Cache::get(self::FAILURES_CACHE_KEY, 0) + 1;
        Cache::put(self::FAILURES_CACHE_KEY, $failures, self::CIRCUIT_OPEN_SECONDS * 2);

        if ($failures >= self::FAILURE_THRESHOLD) {
            Cache::put(self::CIRCUIT_CACHE_KEY, true, self::CIRCUIT_OPEN_SECONDS);
            Cache::forget(self::FAILURES_CACHE_KEY);
            Log::warning('WhatsApp circuit breaker opened after ' . self::FAILURE_THRESHOLD . ' consecutive failures');
        }
    }
}
