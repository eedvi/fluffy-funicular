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
            // Hacer que loan_term_days y due_date sean nullable
            // ya que ahora usamos pago mínimo mensual sin fecha de vencimiento
            $table->integer('loan_term_days')->nullable()->change();
            $table->date('due_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Revertir los cambios (hacer NOT NULL de nuevo)
            $table->integer('loan_term_days')->nullable(false)->change();
            $table->date('due_date')->nullable(false)->change();
        });
    }
};
