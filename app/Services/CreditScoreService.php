<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;

class CreditScoreService
{
    /**
     * Calculate credit score for a customer using FICO model
     * Score range: 300-850
     *
     * FICO requires minimum credit history to generate a score.
     * Returns NULL if customer doesn't have sufficient history.
     */
    public function calculateCreditScore(Customer $customer): array
    {
        // FICO Real: Require minimum history to calculate score
        // At least 1 completed loan (paid or forfeited)
        $completedLoans = $customer->loans()
            ->whereIn('status', [Loan::STATUS_PAID, Loan::STATUS_FORFEITED])
            ->count();

        if ($completedLoans === 0) {
            // No credit history = No score (FICO real behavior)
            return [
                'score' => null,
                'rating' => null,
                'updated_at' => now(),
            ];
        }

        // Calculate score with FICO methodology
        $score = 300; // Minimum possible score (not a "base")

        // Factor 1: Payment History (35% weight) - Most important
        $score += $this->calculatePaymentHistoryScore($customer);

        // Factor 2: Amounts Owed / Credit Utilization (30% weight)
        $score += $this->calculateCreditUtilizationScore($customer);

        // Factor 3: Length of Credit History (15% weight)
        $score += $this->calculateAccountAgeScore($customer);

        // Factor 4: Credit Mix / Loan Performance (10% weight)
        $score += $this->calculateLoanPerformanceScore($customer);

        // Factor 5: New Credit / Recent Activity (10% weight)
        $score += $this->calculateRecentActivityScore($customer);

        // Ensure score is within FICO bounds
        $score = max(300, min(850, $score));

        // Determine rating
        $rating = $this->getRating($score);

        return [
            'score' => round($score),
            'rating' => $rating,
            'updated_at' => now(),
        ];
    }

    /**
     * Payment History Score (35% of total = 297 points max)
     * Most important factor in FICO scoring
     */
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

        $forfeited = $customer->loans()->where('status', 'forfeited')->count();

        $score = 0;

        // Perfect payment history = 297 points (35% of 850)
        if ($paidOnTimeLoans === $totalLoans && $forfeited === 0) {
            $score += 297;
        } else {
            // Calculate based on ratio
            $onTimeRatio = $totalLoans > 0 ? $paidOnTimeLoans / $totalLoans : 0;
            $score += ($onTimeRatio * 297);
        }

        // Penalties (FICO is very strict on payment history)
        $score -= ($latePayments * 30);   // -30 points per late payment
        $score -= ($forfeited * 150);     // -150 points per forfeited loan (severe)

        return max(0, round($score));
    }

    /**
     * Credit Mix / Loan Performance Score (10% of total = 85 points max)
     * Diversity and management of credit types
     */
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

        // Reward for successfully completed loans
        $completionRatio = $paidLoans / $totalLoans;
        $score += ($completionRatio * 85);

        // Small bonus for having active loans (shows current use)
        if ($activeLoans > 0 && $activeLoans <= 2) {
            $score += 10;
        }

        // Penalty for overdue loans (very negative)
        $score -= ($overdueLoans * 40);

        return max(0, round($score));
    }

    /**
     * Credit Utilization Score (30% of total = 255 points max)
     * Measures how much credit is being used vs available
     */
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
            return $currentBalance > 0 ? -100 : 0;
        }

        $utilizationRatio = $currentBalance / $creditLimit;

        // FICO optimal utilization: under 10% is best, under 30% is good
        if ($utilizationRatio <= 0.10) {
            return 255; // Excellent utilization
        } elseif ($utilizationRatio <= 0.30) {
            return 220; // Good utilization
        } elseif ($utilizationRatio <= 0.50) {
            return 150; // Fair utilization
        } elseif ($utilizationRatio <= 0.75) {
            return 80;  // High utilization
        } elseif ($utilizationRatio < 1.0) {
            return 20;  // Very high utilization
        } else {
            return -100; // Over limit! (severe penalty)
        }
    }

    /**
     * Length of Credit History Score (15% of total = 127 points max)
     * Longer credit history is better
     */
    private function calculateAccountAgeScore(Customer $customer): int
    {
        $accountAgeMonths = $customer->created_at->diffInMonths(now());

        // FICO values longer history (7+ years is optimal)
        if ($accountAgeMonths >= 84) { // 7+ years
            return 127;
        } elseif ($accountAgeMonths >= 60) { // 5-7 years
            return 110;
        } elseif ($accountAgeMonths >= 36) { // 3-5 years
            return 90;
        } elseif ($accountAgeMonths >= 24) { // 2-3 years
            return 70;
        } elseif ($accountAgeMonths >= 12) { // 1-2 years
            return 50;
        } elseif ($accountAgeMonths >= 6) { // 6-12 months
            return 30;
        } elseif ($accountAgeMonths >= 3) { // 3-6 months
            return 15;
        }

        return 0; // Less than 3 months
    }

    /**
     * New Credit / Recent Activity Score (10% of total = 85 points max)
     * Recent credit inquiries and new accounts
     */
    private function calculateRecentActivityScore(Customer $customer): int
    {
        $recentLoans = $customer->loans()
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        $recentPayments = $customer->loans()
            ->whereHas('payments', function ($query) {
                $query->where('payment_date', '>=', now()->subMonths(6));
            })
            ->count();

        $score = 0;

        // FICO: too many new accounts is negative, but some activity is good
        if ($recentLoans === 0 && $recentPayments === 0) {
            // No recent activity - neutral
            $score += 40;
        } elseif ($recentLoans <= 2 && $recentPayments > 0) {
            // Moderate activity with payments - good
            $score += 85;
        } elseif ($recentLoans <= 3) {
            // Some activity - okay
            $score += 60;
        } else {
            // Too many new loans - potential risk
            $score += 20;
        }

        return round($score);
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
     * Get recommended credit limit based on credit score and monthly income
     * FICO Real Model: Clients without score get very conservative limits
     * Adjusted for Guatemalan market context (based on Q3,973 average monthly minimum wage)
     */
    public function getRecommendedCreditLimit(Customer $customer): float
    {
        $score = $customer->credit_score; // Can be NULL
        $monthlyIncome = $customer->monthly_income ?? 0;

        // NO CREDIT HISTORY = Very conservative limit (FICO real approach)
        if ($score === null) {
            if ($monthlyIncome <= 0) {
                return 1000; // Minimum limit if no income data
            }
            // New customers: max 1x monthly income, capped at Q5,000
            $newCustomerLimit = min($monthlyIncome, 5000);
            return round($newCustomerLimit, -2); // Round to nearest 100
        }

        // WITH CREDIT HISTORY = Calculate based on score + income
        // Base limits by score range (adjusted for Guatemala 2025)
        if ($score >= 750) {
            // Excellent: ~7.5 monthly salaries
            $scoreLimitBase = 30000;
            $incomeMultiplier = 4.0; // Up to 4x monthly income
        } elseif ($score >= 650) {
            // Good: ~4.5 monthly salaries
            $scoreLimitBase = 18000;
            $incomeMultiplier = 3.0; // Up to 3x monthly income
        } elseif ($score >= 550) {
            // Fair: ~2.5 monthly salaries
            $scoreLimitBase = 10000;
            $incomeMultiplier = 2.0; // Up to 2x monthly income
        } else {
            // Poor: ~1 monthly salary
            $scoreLimitBase = 4000;
            $incomeMultiplier = 1.5; // Up to 1.5x monthly income
        }

        // Calculate income-based limit if monthly income is provided
        if ($monthlyIncome > 0) {
            $incomeBasedLimit = $monthlyIncome * $incomeMultiplier;
            // Take the lower of score-based or income-based limit (conservative approach)
            $baseLimit = min($scoreLimitBase, $incomeBasedLimit);
        } else {
            // If no income data, use score-based limit only
            $baseLimit = $scoreLimitBase;
        }

        // Adjust based on payment history
        $paidLoans = $customer->loans()->where('status', 'paid')->count();
        $multiplier = 1 + (min($paidLoans, 10) * 0.1); // Up to 2x for 10+ paid loans

        // Cap maximum at Q35,000 (realistic for Guatemala market)
        $recommendedLimit = round($baseLimit * $multiplier, -2); // Round to nearest 100
        return min($recommendedLimit, 35000);
    }
}
