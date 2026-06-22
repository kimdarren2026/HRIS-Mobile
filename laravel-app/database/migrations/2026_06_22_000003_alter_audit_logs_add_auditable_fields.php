<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('auditable_type')->nullable()->after('user_id');
            $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');
            $table->json('old_values')->nullable()->after('changes');
            $table->json('new_values')->nullable()->after('old_values');
            $table->text('user_agent')->nullable()->after('ip_address');

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_auditable_index');
            $table->dropColumn(['auditable_type', 'auditable_id', 'old_values', 'new_values', 'user_agent']);
        });
    }
};
