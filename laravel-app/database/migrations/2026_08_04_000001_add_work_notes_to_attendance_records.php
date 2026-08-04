<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->text('check_in_work_plan')->nullable()->after('check_in_photo_path');
            $table->text('check_out_work_result')->nullable()->after('check_out_accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['check_in_work_plan', 'check_out_work_result']);
        });
    }
};
