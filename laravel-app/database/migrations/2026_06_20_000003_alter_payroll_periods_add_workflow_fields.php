<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('calculated_at');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('reviewed_at');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->dateTime('locked_at')->nullable()->after('locked_by');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete()->after('locked_at');
            $table->dateTime('paid_at')->nullable()->after('paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn(['reviewed_at', 'approved_at', 'locked_at', 'paid_at']);
        });
    }
};
