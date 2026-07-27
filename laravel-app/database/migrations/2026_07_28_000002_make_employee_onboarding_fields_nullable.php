<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NULLABLE_COLUMNS = ['nik', 'department_id', 'position_id', 'join_date', 'phone_number'];

    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nik')->nullable()->change();
            $table->foreignId('department_id')->nullable()->change();
            $table->foreignId('position_id')->nullable()->change();
            $table->date('join_date')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
        });
    }

    /**
     * Guarded rollback: restoring NOT NULL on a column that already holds
     * NULL rows would either fail outright or (on some drivers) silently
     * coerce/drop data. Refuse instead of risking a rollback that reports
     * success while leaving the schema (or data) in a broken state.
     */
    public function down(): void
    {
        $hasIncompleteRows = DB::table('employees')
            ->where(function ($query) {
                foreach (self::NULLABLE_COLUMNS as $column) {
                    $query->orWhereNull($column);
                }
            })
            ->exists();

        if ($hasIncompleteRows) {
            throw new RuntimeException(
                'Cannot roll back 2026_07_28_000002_make_employee_onboarding_fields_nullable: '
                .'the employees table has rows with NULL in one of ['.implode(', ', self::NULLABLE_COLUMNS).']. '
                .'Backfill those rows before this migration can restore the NOT NULL constraints. '
                .'No schema changes were made.'
            );
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('nik')->nullable(false)->change();
            $table->foreignId('department_id')->nullable(false)->change();
            $table->foreignId('position_id')->nullable(false)->change();
            $table->date('join_date')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
        });
    }
};
