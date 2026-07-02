<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Chargeable days = working days (Mon-Fri, minus national holidays)
            // for most leave types, or full calendar days for leave types with
            // counts_calendar_days = true. Nullable/unbackfilled for pre-existing
            // rows; LeaveService falls back to total_days for those.
            $table->decimal('chargeable_days', 5, 2)->nullable()->after('total_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('chargeable_days');
        });
    }
};
