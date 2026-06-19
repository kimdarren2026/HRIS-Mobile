<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->json('snapshot_data');
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
