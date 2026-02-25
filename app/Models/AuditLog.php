<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'target_entity_type',
        'target_entity_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, $user = null, string $entityType = null, int $entityId = null, array $details = []): void
    {
        static::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'target_entity_type' => $entityType,
            'target_entity_id' => $entityId,
            'details' => $details,
        ]);
    }
}
