<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $request_code
 * @property int $requester_id
 * @property string $title
 * @property string|null $description
 * @property string $priority
 * @property int|null $equipment_id
 * @property int|null $sensor_id
 * @property string $status
 * @property string|null $bypass_type
 * @property string|null $criticite
 * @property string|null $duree_type
 * @property \Carbon\Carbon $submitted_at
 * @property string $impact_securite
 * @property string $impact_operationnel
 * @property string $impact_environnemental
 * @property string $validation_required_by_role
 * @property int|null $validated_by_id
 * @property \Carbon\Carbon|null $validated_at
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $start_time
 * @property \Carbon\Carbon|null $end_time
 * @property string $mesure_attenuation
 * @property string|null $plan_contingence
 * @property string|null $commentaires
 * @property bool $requires_moc
 * @property \Carbon\Carbon|null $moc_triggered_at
 * @property int|null $validated_by_level1_id
 * @property \Carbon\Carbon|null $validated_at_level1
 * @property string|null $validation_status_level1
 * @property string|null $rejection_reason_level1
 * @property int|null $validated_by_level2_id
 * @property \Carbon\Carbon|null $validated_at_level2
 * @property string|null $validation_status_level2
 * @property string|null $rejection_reason_level2
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User|null $requester
 * @property-read User|null $validator
 * @property-read User|null $validatorLevel1
 * @property-read User|null $validatorLevel2
 * @property-read Equipment|null $equipment
 * @property-read Sensor|null $sensor
 * @property-read Ora|null $ora
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RequestApproval> $approvals
 */
class Request extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'request_code',
        'requester_id',
        'title',
        'description',
        'priority',
        'equipment_id',
        'sensor_id',
        'status',
        'bypass_type',
        'criticite',
        'duree_type',
        'submitted_at',
        'impact_securite',
        'impact_operationnel',
        'impact_environnemental',
        'validation_required_by_role',
        'validated_by_id',
        'validated_at',
        'rejection_reason',
        'start_time',
        'end_time',
        'mesure_attenuation',
        'plan_contingence',
        'commentaires',
        'requires_moc',
        'moc_triggered_at',
        'validated_by_level1_id',
        'validated_at_level1',
        'validation_status_level1',
        'rejection_reason_level1',
        'validated_by_level2_id',
        'validated_at_level2',
        'validation_status_level2',
        'rejection_reason_level2',
        'expiration_warned_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'validated_at_level1' => 'datetime',
        'validated_at_level2' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'moc_triggered_at' => 'datetime',
        'requires_moc' => 'boolean',
        'expiration_warned_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_code)) {
                $request->request_code = static::generateRequestCode();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function validatorLevel1()
    {
        return $this->belongsTo(User::class, 'validated_by_level1_id');
    }

    public function validatorLevel2()
    {
        return $this->belongsTo(User::class, 'validated_by_level2_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function ora()
    {
        return $this->hasOne(Ora::class);
    }

    public function approvals()
    {
        return $this->hasMany(RequestApproval::class)->orderBy('level');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'approved']);
    }

    public function scopeActiveOnly($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeApprovedToday($query)
    {
        return $query->where('status', 'approved')
                    ->whereDate('validated_at', today());
    }

    // ── Business Logic ───────────────────────────────────────────

    /**
     * Legacy: checks if dual validation needed based on priority
     */
    public function requiresDualValidation(): bool
    {
        return in_array($this->priority, ['critical', 'emergency']);
    }

    public function hasBothApprovals(): bool
    {
        if (!$this->requiresDualValidation()) {
            return $this->status === 'approved';
        }

        return $this->validation_status_level1 === 'approved'
            && $this->validation_status_level2 === 'approved';
    }

    public function hasRejection(): bool
    {
        if (!$this->requiresDualValidation()) {
            return $this->status === 'rejected';
        }

        return $this->validation_status_level1 === 'rejected'
            || $this->validation_status_level2 === 'rejected';
    }

    /**
     * CDC: checks if ORA is required (security criticality)
     */
    public function requiresOra(): bool
    {
        return $this->criticite === 'securite';
    }

    /**
     * CDC: checks if all approval steps are complete
     */
    public function allApprovalsComplete(): bool
    {
        if ($this->approvals->isEmpty()) {
            return $this->hasBothApprovals();
        }

        return $this->approvals->every(fn ($a) => $a->status === 'approved');
    }

    /**
     * CDC: checks if any approval was rejected
     */
    public function hasAnyRejection(): bool
    {
        if ($this->approvals->isEmpty()) {
            return $this->hasRejection();
        }

        return $this->approvals->contains(fn ($a) => $a->status === 'rejected');
    }

    /**
     * CDC: get the next pending approval step
     */
    public function nextPendingApproval(): ?RequestApproval
    {
        return $this->approvals->where('status', 'pending')->sortBy('level')->first();
    }

    private static function generateRequestCode(): string
    {
        $year = date('Y');
        $prefix = "BYP-{$year}-";

        $lastRequest = static::where('request_code', 'like', $prefix . '%')
                           ->orderBy('request_code', 'desc')
                           ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
