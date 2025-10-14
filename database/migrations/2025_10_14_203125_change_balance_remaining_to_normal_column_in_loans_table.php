<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Drop the generated column
            $table->dropColumn('balance_remaining');
        });

        Schema::table('loans', function (Blueprint $table) {
            // Add it back as a normal column with default value
            $table->decimal('balance_remaining', 10, 2)->default(0)->after('amount_paid');
        });

        // Update existing records to calculate balance_remaining
        DB::statement('UPDATE loans SET balance_remaining = total_amount - amount_paid');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Drop the normal column
            $table->dropColumn('balance_remaining');
        });

        Schema::table('loans', function (Blueprint $table) {
            // Add it back as a generated column
            $table->decimal('balance_remaining', 10, 2)->storedAs('total_amount - amount_paid');
        });
    }
};
