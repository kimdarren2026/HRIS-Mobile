<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->string('action_url')->nullable()->after('category');
            // Extend the type enum to include 'expense' for both SQLite (CHECK) and MySQL
            $table->enum('type', ['attendance', 'leave', 'payroll', 'expense', 'general'])
                ->default('general')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['category', 'action_url']);
            $table->enum('type', ['attendance', 'leave', 'payroll', 'general'])
                ->default('general')
                ->change();
        });
    }
};
