<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->unsignedInteger('attendance_days')->nullable()->after('net_salary');
            $table->decimal('leave_days', 5, 2)->nullable()->after('attendance_days');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropColumn(['attendance_days', 'leave_days']);
        });
    }
};
