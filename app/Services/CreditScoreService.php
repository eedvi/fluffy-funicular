<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;

class CreditScoreService
{
    /**
     * Calculate credit score for a customer
     * Score range: 300-850 (similar to FICO)
     */
    public function calculateCreditScore(Customer $customer): array
    {
        $score = 500; // Base score

        // Factor 1: Payment History (35% weight)
        $score += $this->calculatePaymentHistoryScore($customer);

        // Factor 2: Loan Performance (30% weight)
        $score += $this->calculateLoanPerformanceScore($customer);

        // Factor 3: Credit Utilization (20% weight)
        $score += $this->calculateCreditUtilizationScore($customer);

        // Factor 4: Account Age (10% weight)
        $score += $this->calculateAccountAgeScore($customer);

        // Factor 5: Recent Activity (5% weight)
        $score += $this->calculateRecentActivityScore($customer);

        // Ensure score is within bounds
        $score = max(300, min(850, $score));

        // Determine rating
        $rating = $this->getRating($score);

        return [
            'score' => round($score),
            'rating' => $rating,
            'updated_at' => now(),
        ];
    }

    private function calculatePaymentHistoryScore(Customer $customer): int
    {
        $totalLoans = $customer->loans()->count();

        if ($totalLoans === 0) {
            return 0;
        }

        $paidOnTimeLoans = $customer->loans()
            ->where('status', 'paid')
            ->whereColumn('paid_date', '<=', 'due_date')
            ->count();

        $latePayments = $customer->loans()
            ->where('status', 'paid')
            ->whereColumn('paid_date', '>', 'due_date')
            ->count();

        $defaulted = $customer->loans()->where('status', 'defaulted')->count();

        $score = 0;

        // Perfect payment history
        if ($paidOnTimeLoans === $totalLoans) {
            $score += 150;
        } else {
            // Calculate based on ratio
            $onTimeRatio = $totalLoans > 0 ? $paidOnTimeLoans / $totalLoans : 0;
            $score += ($onTimeRatio * 150);
        }

        // Penalties
        $score -= ($latePayments * 15);  // -15 points per late payment
        $score -= ($defaulted * 100);     // -100 points per default

        return round($score);
    }

    private function calculateLoanPerformanceScore(Customer $customer): int
    {
        $totalLoans = $customer->loans()->count();

        if ($totalLoans === 0) {
            return 0;
        }

        $paidLoans = $customer->loans()->where('status', Loan::STATUS_PAID)->count();
        $activeLoans = $customer->loans()->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_PENDING])->count();
        $overdueLoans = $customer->loans()->where('status', Loan::STATUS_OVERDUE)->count();

        $score = 0;

        // Reward for completed loans
        $score += min($paidLoans * 20, 100);

        // Small bonus for active loans (shows trust)
        $score += min($activeLoans * 10, 30);

        // Penalty for overdue
        $score -= ($overdueLoans * 50);

        return round($score);
    }

    private function calculateCreditUtilizationScore(Customer $customer): int
    {
        $creditLimit = $customer->credit_limit ?? 0;

        if ($creditLimit <= 0) {
            return 0;
        }

        $currentBalance = $customer->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_PENDING])
            ->sum('balance_remaining');

        // Safety check for division by zero
        if ($creditLimit == 0) {
            return $currentBalance > 0 ? -50 : 0;
        }

        $utilizationRatio = $currentBalance / $creditLimit;

        // Best ratio is under 30%
        if ($utilizationRatio <= 0.30) {
            return 100;
        } elseif ($utilizationRatio <= 0.50) {
            return 70;
        } elseif ($utilizationRatio <= 0.75) {
            return 40;
        } elseif ($utilizationRatio < 1.0) {
            return 10;
        } else {
            return -50; // Over limit!
        }
    }

    private function calculateAccountAgeScore(Customer $customer): int
    {
        $accountAgeMonths = $customer->created_at->diffInMonths(now());

        // Longer account age = better
        if ($accountAgeMonths >= 24) {
            return 50;
        } elseif ($accountAgeMonths >= 12) {
            return 35;
        } elseif ($accountAgeMonths >= 6) {
            return 20;
        } elseif ($accountAgeMonths >= 3) {
            return 10;
        }

        return 0;
    }

    private function calculateRecentActivityScore(Customer $customer): int
    {
        $recentLoans = $customer->loans()
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();

        $recentPayments = $customer->loans()
            ->whereHas('payments', function ($query) {
                $query->where('payment_date', '>=', now()->subMonths(3));
            })
            ->count();

        // Small bonus for recent activity
        return min(($recentLoans + $recentPayments) * 5, 25);
    }

    private function getRating(int $score): string
    {
        if ($score >= 750) {
            return 'excellent';
        } elseif ($score >= 650) {
            return 'good';
        } elseif ($score >= 550) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Update credit score for a customer
     */
    public function updateCustomerCreditScore(Customer $customer): void
    {
        $result = $this->calculateCreditScore($customer);

        $customer->update([
            'credit_score' => $result['score'],
            'credit_rating' => $result['rating'],
            'credit_score_updated_at' => $result['updated_at'],
        ]);
    }

    /**
     * Get recommended credit limit based on credit score
     */
    public function getRecommendedCreditLimit(Customer $customer): float
    {
        $score = $customer->credit_score ?? 500;

        // Base limits by score range
        if ($score >= 750) {
            $baseLimit = 50000;
        } elseif ($score >= 650) {
            $baseLimit = 25000;
        } elseif ($score >= 550) {
            $baseLimit = 10000;
        } else {
            $baseLimit = 5000;
        }

        // Adjust based on payment history
        $paidLoans = $customer->loans()->where('status', 'paid')->count();
        $multiplier = 1 + (min($paidLoans, 10) * 0.1); // Up to 2x for 10+ paid loans

        return round($baseLimit * $multiplier, -2); // Round to nearest 100
    }
}
