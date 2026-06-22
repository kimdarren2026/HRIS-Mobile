<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_expenses', function (Blueprint $table): void {
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('company_expenses', function (Blueprint $table): void {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejected_by', 'rejected_at']);
        });
    }
};
