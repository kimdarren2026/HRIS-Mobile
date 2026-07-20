<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TYPES = ['attendance', 'leave', 'payroll', 'general'];

    private const NEW_TYPES = ['attendance', 'leave', 'payroll', 'general', 'expense'];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite has no ALTER COLUMN for CHECK constraints. Instead of touching
            // sqlite_master or recreating the table with raw SQL, use the Schema
            // Builder to change the column to a plain string — Laravel's SQLite
            // grammar handles this natively via change(), and a string column has
            // no CHECK constraint to keep in sync with the allowed values list.
            // This only affects the SQLite (test) database; existing rows are kept
            // as-is by Laravel's column-change machinery.
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('type')->default('general')->change();
            });

            return;
        }

        // MySQL/MariaDB: metadata-only ALTER on the existing column. No rows are
        // touched, no table is dropped or recreated.
        DB::statement('ALTER TABLE notifications MODIFY COLUMN type ENUM(' . $this->enumList(self::NEW_TYPES) . ") NOT NULL DEFAULT 'general'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // No safe, non-destructive way to restore the CHECK constraint on SQLite
            // without recreating the table, which is intentionally out of scope here.
            return;
        }

        $expenseCount = DB::table('notifications')->where('type', 'expense')->count();

        if ($expenseCount > 0) {
            throw new \RuntimeException(
                "Rollback dibatalkan: {$expenseCount} baris notifications masih memakai type='expense'. ".
                'Hapus atau ubah baris tersebut secara manual terlebih dahulu sebelum rollback migration ini. '.
                'Data yang sudah ada tidak diubah oleh migration ini.'
            );
        }

        DB::statement('ALTER TABLE notifications MODIFY COLUMN type ENUM(' . $this->enumList(self::OLD_TYPES) . ") NOT NULL DEFAULT 'general'");
    }

    /**
     * @param  string[]  $types
     */
    private function enumList(array $types): string
    {
        return "'" . implode("', '", $types) . "'";
    }
};
