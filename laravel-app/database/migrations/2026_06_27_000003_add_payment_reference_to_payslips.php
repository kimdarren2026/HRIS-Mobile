<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table): void {
            $table->string('payment_reference')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table): void {
            $table->dropColumn('payment_reference');
        });
    }
};
