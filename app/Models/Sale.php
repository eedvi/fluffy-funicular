<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Sale extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'sale_number',
        'item_id',
        'customer_id',
        'sale_price',
        'discount',
        'final_price',
        'sale_date',
        'payment_method',
        'invoice_number',
        'status',
        'delivery_date',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'sale_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_id', 'customer_id', 'final_price', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Static methods
    public static function generateSaleNumber(): string
    {
        $prefix = 'S-';
        $date = now()->format('Ymd');
        $lastSale = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastSale ? (int) substr($lastSale->sale_number, -4) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
