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
        Schema::table('items', function (Blueprint $table) {
            // Drop the existing unique constraint
            $table->dropUnique(['serial_number']);
        });

        // SQLite doesn't support partial indexes in the same way as PostgreSQL
        // We'll handle uniqueness validation in the application layer instead
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique('serial_number');
        });
    }
};
