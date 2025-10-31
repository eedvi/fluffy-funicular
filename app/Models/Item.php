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
        'category_id',
        'customer_id',
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
        'confiscated_date',
        'auction_price',
        'auction_date',
        'confiscation_notes',
    ];

    protected $casts = [
        'appraised_value' => 'decimal:2',
        'market_value' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'auction_price' => 'decimal:2',
        'acquired_date' => 'date',
        'confiscated_date' => 'date',
        'auction_date' => 'date',
    ];

    protected $attributes = [
        'photos' => '[]',
    ];

    // Accessor to ensure photos is always an array
    protected function photos(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => is_string($value) ? json_decode($value, true) ?? [] : ($value ?? []),
            set: fn ($value) => is_array($value) ? json_encode($value) : ($value ?? '[]'),
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'category_id', 'status', 'appraised_value'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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

    public function transfers(): HasMany
    {
        return $this->hasMany(ItemTransfer::class);
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
