<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'mobile',
        'email',
        'identity_type',
        'identity_number',
        'identity_expiry',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'occupation',
        'employer',
        'monthly_income',
        'credit_limit',
        'credit_score',
        'credit_rating',
        'credit_score_updated_at',
        'emergency_contact_name',
        'emergency_contact_phone',
        'registration_date',
        'is_active',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'identity_expiry' => 'date',
        'registration_date' => 'date',
        'monthly_income' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'identity_number', 'phone', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->whereIn('status', ['active', 'overdue']);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Loan::class);
    }

    public function loyaltyProgram()
    {
        return $this->hasOne(LoyaltyProgram::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
