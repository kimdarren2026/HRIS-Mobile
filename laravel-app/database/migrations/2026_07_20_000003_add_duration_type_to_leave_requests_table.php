<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Half-day leave policy correction: half-day is now a supported
            // duration_type (not a fractional total_days/chargeable_days value).
            // total_days and chargeable_days stay whole-number decimals as
            // before — no column type change needed for either this table or
            // leave_balances (already decimal(5,2), sufficient for whole days).
            $table->enum('duration_type', ['FULL_DAY', 'HALF_DAY'])
                ->default('FULL_DAY')
                ->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('duration_type');
        });
    }
};
