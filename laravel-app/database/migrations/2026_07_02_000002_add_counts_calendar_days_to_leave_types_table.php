<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Maternity/miscarriage leave counts national holidays and collective
            // leave as part of the leave period (STIKES policy point 3 exception).
            // All other leave types default to false: weekends and national
            // holidays are excluded from the chargeable day count.
            $table->boolean('counts_calendar_days')->default(false)->after('deducts_balance');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('counts_calendar_days');
        });
    }
};
