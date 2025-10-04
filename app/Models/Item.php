<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ScopedBy([BranchScope::class])]
class Item extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'category',
        'brand',
        'model',
        'serial_number',
        'condition',
        'appraised_value',
        'market_value',
        'purchase_price',
        'sale_price',
        'status',
        'location',
        'photos',
        'notes',
        'acquired_date',
        'branch_id',
    ];

    protected $casts = [
        'appraised_value' => 'decimal:2',
        'market_value' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'acquired_date' => 'date',
        'photos' => 'array',
    ];

    protected $attributes = [
        'photos' => '[]',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'category', 'status', 'appraised_value'])
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

    public function currentLoan(): HasOne
    {
        return $this->hasOne(Loan::class)->whereIn('status', ['active', 'overdue'])->latestOfMany();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeCollateral($query)
    {
        return $query->where('status', 'collateral');
    }

    public function scopeForSale($query)
    {
        return $query->whereIn('status', ['available', 'forfeited']);
    }
}
