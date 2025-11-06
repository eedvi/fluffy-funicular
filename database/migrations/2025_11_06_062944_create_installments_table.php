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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');

            // Información de la cuota
            $table->integer('installment_number'); // 1, 2, 3...
            $table->date('due_date'); // Fecha de vencimiento

            // Montos
            $table->decimal('amount', 10, 2); // Monto total de la cuota
            $table->decimal('principal_amount', 10, 2); // Parte del capital
            $table->decimal('interest_amount', 10, 2); // Parte del interés
            $table->decimal('paid_amount', 10, 2)->default(0); // Monto pagado
            $table->decimal('balance_remaining', 10, 2); // Saldo pendiente de esta cuota

            // Mora
            $table->decimal('late_fee', 10, 2)->default(0); // Cargo por mora
            $table->integer('days_overdue')->default(0); // Días de retraso

            // Estado
            $table->string('status')->default('pending'); // pending, paid, overdue, partially_paid
            $table->date('paid_date')->nullable(); // Fecha de pago completo

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['loan_id', 'installment_number']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
