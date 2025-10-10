<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update loan status values from Spanish to English
        DB::table('loans')->where('status', 'Activo')->update(['status' => 'active']);
        DB::table('loans')->where('status', 'Pagado')->update(['status' => 'paid']);
        DB::table('loans')->where('status', 'Vencido')->update(['status' => 'overdue']);
        DB::table('loans')->where('status', 'Confiscado')->update(['status' => 'forfeited']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to Spanish values if needed
        DB::table('loans')->where('status', 'active')->update(['status' => 'Activo']);
        DB::table('loans')->where('status', 'paid')->update(['status' => 'Pagado']);
        DB::table('loans')->where('status', 'overdue')->update(['status' => 'Vencido']);
        DB::table('loans')->where('status', 'forfeited')->update(['status' => 'Confiscado']);
    }
};
