<?php

namespace App\Jobs;

use App\Contracts\MessagingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private string $phone,
        private string $message,
    ) {}

    public function handle(MessagingServiceInterface $whapiService): void
    {
        if (empty(config('services.whapi.token'))) {
            Log::debug('SendWhatsAppNotification skipped: no Whapi token configured');
            return;
        }

        try {
            $whapiService->sendTextMessage($this->phone, $this->message);
        } catch (\Exception $e) {
            Log::error('Job SendWhatsAppNotification failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e;
        }
    }
}
