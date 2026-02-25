<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $request_id
 * @property string $required_role
 * @property int $level
 * @property int|null $approved_by_id
 * @property \Carbon\Carbon|null $approved_at
 * @property string $status
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Request $request
 * @property-read User|null $approvedBy
 */
class RequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'required_role',
        'level',
        'approved_by_id',
        'approved_at',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'level' => 'integer',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
