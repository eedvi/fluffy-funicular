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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('item_id')->constrained()->onDelete('restrict');
            $table->decimal('loan_amount', 10, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('loan_term_days');
            $table->date('start_date');
            $table->date('due_date');
            $table->decimal('interest_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_remaining', 10, 2)->storedAs('total_amount - amount_paid');
            $table->enum('status', ['pending', 'active', 'paid', 'overdue', 'defaulted'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->date('forfeited_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('loan_number');
            $table->index('customer_id');
            $table->index('item_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
