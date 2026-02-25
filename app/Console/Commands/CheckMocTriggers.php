<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Request;
use App\Models\User;
use Illuminate\Console\Command;

class CheckMocTriggers extends Command
{
    protected $signature = 'app:check-moc-triggers';

    protected $description = 'Check active bypasses > 72h and trigger MOC if needed';

    public function handle(): void
    {
        $activeOver72h = Request::where('status', RequestStatus::Active->value)
            ->where('requires_moc', false)
            ->where('start_time', '<', now()->subHours(72))
            ->with('requester')
            ->get();

        foreach ($activeOver72h as $request) {
            $request->update([
                'requires_moc' => true,
                'moc_triggered_at' => now(),
            ]);

            $message = "🔴 *Alerte MOC*\n" .
                "📝 Code : {$request->request_code}\n" .
                "⚠️ Le bypass est actif depuis plus de 72h.\n" .
                "Un Management of Change (MOC) doit être initié.\n" .
                "📅 Activé depuis : " . $request->start_time->format('d/m/Y H:i');

            // Notify directors
            $directors = User::whereIn('role', [
                    User::ROLE_DIRECTEUR, User::ROLE_ADMINISTRATEUR,
                    'director', 'administrator',
                ])
                ->whereNotNull('phone')
                ->get();

            foreach ($directors as $user) {
                $phone = ltrim($user->phone, '+');
                SendWhatsAppNotification::dispatch($phone, $message);
            }
        }

        if ($activeOver72h->count() > 0) {
            $this->info("MOC triggered for {$activeOver72h->count()} bypass(es).");
        }
    }
}
