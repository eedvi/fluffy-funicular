<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->date('confiscated_date')->nullable()->after('status');
            $table->decimal('auction_price', 10, 2)->nullable()->after('confiscated_date');
            $table->date('auction_date')->nullable()->after('auction_price');
            $table->text('confiscation_notes')->nullable()->after('auction_date');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['confiscated_date', 'auction_price', 'auction_date', 'confiscation_notes']);
        });
    }
};
