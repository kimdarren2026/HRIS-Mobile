<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->date('pay_date')->nullable()->after('end_date');
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            $table->dateTime('calculated_at')->nullable()->after('calculated_by');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('calculated_by');
            $table->dropColumn(['pay_date', 'calculated_at']);
        });
    }
};
