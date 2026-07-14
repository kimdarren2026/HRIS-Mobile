<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addGoogleLinkedAt = ! Schema::hasColumn('users', 'google_linked_at');
        $addLastLoginIp = ! Schema::hasColumn('users', 'last_login_ip');
        $addLastLoginProvider = ! Schema::hasColumn('users', 'last_login_provider');

        if (! $addGoogleLinkedAt && ! $addLastLoginIp && ! $addLastLoginProvider) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($addGoogleLinkedAt, $addLastLoginIp, $addLastLoginProvider): void {
            if ($addGoogleLinkedAt) {
                $table->timestamp('google_linked_at')->nullable()->after('google_id');
            }

            if ($addLastLoginIp) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if ($addLastLoginProvider) {
                $table->string('last_login_provider', 50)->nullable()->after('last_login_ip');
            }
        });
    }

    public function down(): void
    {
        $dropGoogleLinkedAt = Schema::hasColumn('users', 'google_linked_at');
        $dropLastLoginIp = Schema::hasColumn('users', 'last_login_ip');
        $dropLastLoginProvider = Schema::hasColumn('users', 'last_login_provider');

        if (! $dropGoogleLinkedAt && ! $dropLastLoginIp && ! $dropLastLoginProvider) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($dropGoogleLinkedAt, $dropLastLoginIp, $dropLastLoginProvider): void {
            $columns = [];

            if ($dropGoogleLinkedAt) {
                $columns[] = 'google_linked_at';
            }

            if ($dropLastLoginIp) {
                $columns[] = 'last_login_ip';
            }

            if ($dropLastLoginProvider) {
                $columns[] = 'last_login_provider';
            }

            $table->dropColumn($columns);
        });
    }
};
