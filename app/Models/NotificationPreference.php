<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'event_type',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function isEnabled(int $userId, string $channel, string $eventType): bool
    {
        $pref = static::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('event_type', $eventType)
            ->first();

        // Default to enabled if no preference exists
        return $pref ? $pref->enabled : true;
    }
}
