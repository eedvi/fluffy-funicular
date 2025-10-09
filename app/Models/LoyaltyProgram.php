<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgram extends Model
{
    protected $fillable = [
        'customer_id',
        'tier',
        'points',
        'points_lifetime',
        'rewards_earned',
        'rewards_redeemed',
        'tier_achieved_at',
        'last_activity_at',
        'notes',
    ];

    protected $casts = [
        'tier_achieved_at' => 'date',
        'last_activity_at' => 'date',
    ];

    // Tier thresholds (lifetime points required)
    const TIER_THRESHOLDS = [
        'bronze' => 0,
        'silver' => 1000,
        'gold' => 5000,
        'platinum' => 15000,
    ];

    // Points earned per action
    const POINTS_PER_PAYMENT = 10;        // 10 points per on-time payment
    const POINTS_PER_LOAN = 50;           // 50 points per new loan
    const POINTS_PER_REFERRAL = 500;      // 500 points per referral

    // Benefits per tier (interest rate discount %)
    const TIER_BENEFITS = [
        'bronze' => [
            'interest_discount' => 0,
            'late_fee_waiver' => 0,
            'priority_support' => false,
        ],
        'silver' => [
            'interest_discount' => 0.5,
            'late_fee_waiver' => 1,
            'priority_support' => false,
        ],
        'gold' => [
            'interest_discount' => 1.0,
            'late_fee_waiver' => 2,
            'priority_support' => true,
        ],
        'platinum' => [
            'interest_discount' => 1.5,
            'late_fee_waiver' => 3,
            'priority_support' => true,
        ],
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Add points to the loyalty program
     */
    public function addPoints(int $points, string $reason = null): void
    {
        $this->points += $points;
        $this->points_lifetime += $points;
        $this->last_activity_at = now();

        // Check if tier should be upgraded
        $newTier = $this->calculateTier();
        if ($newTier !== $this->tier) {
            $this->tier = $newTier;
            $this->tier_achieved_at = now();
        }

        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . now()->format('Y-m-d H:i') . ": +{$points} puntos - {$reason}";
        }

        $this->save();
    }

    /**
     * Redeem points for a reward
     */
    public function redeemPoints(int $points, string $reward = null): bool
    {
        if ($this->points < $points) {
            return false;
        }

        $this->points -= $points;
        $this->rewards_redeemed++;
        $this->last_activity_at = now();

        if ($reward) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . now()->format('Y-m-d H:i') . ": -{$points} puntos - {$reward}";
        }

        $this->save();

        return true;
    }

    /**
     * Calculate tier based on lifetime points
     */
    protected function calculateTier(): string
    {
        if ($this->points_lifetime >= self::TIER_THRESHOLDS['platinum']) {
            return 'platinum';
        } elseif ($this->points_lifetime >= self::TIER_THRESHOLDS['gold']) {
            return 'gold';
        } elseif ($this->points_lifetime >= self::TIER_THRESHOLDS['silver']) {
            return 'silver';
        }

        return 'bronze';
    }

    /**
     * Get interest discount for this tier
     */
    public function getInterestDiscount(): float
    {
        return self::TIER_BENEFITS[$this->tier]['interest_discount'] ?? 0;
    }

    /**
     * Get number of late fee waivers available
     */
    public function getLateFeeWaivers(): int
    {
        return self::TIER_BENEFITS[$this->tier]['late_fee_waiver'] ?? 0;
    }

    /**
     * Check if has priority support
     */
    public function hasPrioritySupport(): bool
    {
        return self::TIER_BENEFITS[$this->tier]['priority_support'] ?? false;
    }

    /**
     * Get tier color for display
     */
    public function getTierColor(): string
    {
        return match ($this->tier) {
            'platinum' => 'purple',
            'gold' => 'warning',
            'silver' => 'gray',
            'bronze' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get tier label
     */
    public function getTierLabel(): string
    {
        return match ($this->tier) {
            'platinum' => 'Platino',
            'gold' => 'Oro',
            'silver' => 'Plata',
            'bronze' => 'Bronce',
            default => 'Bronce',
        };
    }

    /**
     * Get points needed for next tier
     */
    public function getPointsToNextTier(): ?int
    {
        $nextTier = match ($this->tier) {
            'bronze' => 'silver',
            'silver' => 'gold',
            'gold' => 'platinum',
            default => null,
        };

        if (!$nextTier) {
            return null; // Already at max tier
        }

        return self::TIER_THRESHOLDS[$nextTier] - $this->points_lifetime;
    }
}
