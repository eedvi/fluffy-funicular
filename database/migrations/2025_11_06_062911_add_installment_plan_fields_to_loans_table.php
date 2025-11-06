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
            // Tipo de plan de pago: 'minimum_payment' o 'installments'
            $table->string('payment_plan_type')->default('minimum_payment')->after('grace_period_days');

            // Campos para plan de cuotas
            $table->integer('number_of_installments')->nullable()->after('payment_plan_type');
            $table->decimal('installment_amount', 10, 2)->nullable()->after('number_of_installments');
            $table->integer('installment_frequency_days')->default(30)->after('installment_amount');
            $table->decimal('late_fee_percentage', 5, 2)->default(5.00)->after('installment_frequency_days')
                ->comment('Porcentaje de mora por cuota vencida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'payment_plan_type',
                'number_of_installments',
                'installment_amount',
                'installment_frequency_days',
                'late_fee_percentage',
            ]);
        });
    }
};
