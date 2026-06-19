<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nik')->unique();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->date('join_date');
            $table->enum('employment_status', ['active', 'probation', 'resigned', 'terminated'])->default('active');
            $table->string('phone_number');
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
