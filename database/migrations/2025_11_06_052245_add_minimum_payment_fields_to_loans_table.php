<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Minimum monthly payment configuration
            $table->decimal('minimum_monthly_payment', 10, 2)->nullable()->after('balance_remaining');
            $table->boolean('requires_minimum_payment')->default(false)->after('minimum_monthly_payment');

            // Payment tracking
            $table->date('next_minimum_payment_date')->nullable()->after('requires_minimum_payment');
            $table->date('last_minimum_payment_date')->nullable()->after('next_minimum_payment_date');

            // Risk tracking
            $table->boolean('is_at_risk')->default(false)->after('last_minimum_payment_date');
            $table->date('grace_period_end_date')->nullable()->after('is_at_risk');
            $table->integer('consecutive_missed_payments')->default(0)->after('grace_period_end_date');
            $table->integer('grace_period_days')->default(5)->after('consecutive_missed_payments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'minimum_monthly_payment',
                'requires_minimum_payment',
                'next_minimum_payment_date',
                'last_minimum_payment_date',
                'is_at_risk',
                'grace_period_end_date',
                'consecutive_missed_payments',
                'grace_period_days',
            ]);
        });
    }
};
