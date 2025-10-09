<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ItemTransfer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'transfer_number',
        'item_id',
        'from_branch_id',
        'to_branch_id',
        'transferred_by',
        'received_by',
        'transfer_date',
        'received_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'received_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_id', 'from_branch_id', 'to_branch_id', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    // Static methods
    public static function generateTransferNumber(): string
    {
        $prefix = 'T-';
        $date = now()->format('Ymd');
        $lastTransfer = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastTransfer ? (int) substr($lastTransfer->transfer_number, -4) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Methods
    public function markAsInTransit(): void
    {
        $this->update([
            'status' => 'in_transit',
        ]);
    }

    public function markAsReceived(User $receivedBy): void
    {
        // Update item branch when transfer is received
        $this->item->update([
            'branch_id' => $this->to_branch_id,
        ]);

        $this->update([
            'status' => 'received',
            'received_by' => $receivedBy->id,
            'received_date' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
