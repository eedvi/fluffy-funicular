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
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->integer('points')->default(0);
            $table->integer('points_lifetime')->default(0);
            $table->integer('rewards_earned')->default(0);
            $table->integer('rewards_redeemed')->default(0);
            $table->date('tier_achieved_at')->nullable();
            $table->date('last_activity_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('tier');
            $table->index('points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
