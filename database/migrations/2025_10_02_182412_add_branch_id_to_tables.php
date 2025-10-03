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
        // Add branch_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->onDelete('restrict');
        });

        // Add branch_id to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->onDelete('restrict');
        });

        // Add branch_id to loans table
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->onDelete('restrict');
        });

        // Add branch_id to items table
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->onDelete('restrict');
        });

        // Add branch_id to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->onDelete('restrict');
        });

        // Add branch_id to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
