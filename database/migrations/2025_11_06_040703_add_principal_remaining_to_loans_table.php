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
            // Add principal_remaining field to track the remaining capital (without interest)
            $table->decimal('principal_remaining', 10, 2)->default(0)->after('balance_remaining');
        });

        // Initialize principal_remaining with loan_amount for existing loans
        DB::statement('UPDATE loans SET principal_remaining = loan_amount');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('principal_remaining');
        });
    }
};
