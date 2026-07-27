<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Phase 58C: leave_balances already has year, entitlement (total_quota),
    // used, remaining and the unique (employee_id, leave_type_id, year)
    // constraint — those are reused as-is. This migration only adds the
    // fields needed to record and audit an opening/transition balance
    // (HRIS starting mid-year, pre-system usage, manual adjustments).
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table): void {
            $table->decimal('pre_system_used_days', 5, 2)->default(0)->after('remaining');
            $table->decimal('opening_adjustment', 5, 2)->default(0)->after('pre_system_used_days');
            $table->date('effective_date')->nullable()->after('opening_adjustment');
            $table->text('reason')->nullable()->after('effective_date');
            $table->foreignId('created_by')->nullable()->after('reason')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['pre_system_used_days', 'opening_adjustment', 'effective_date', 'reason']);
        });
    }
};
