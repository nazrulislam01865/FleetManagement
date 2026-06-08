<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'account_status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_status', 20)
                ->default('active')
                ->after('fleet_role_id')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'account_status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['account_status']);
            $table->dropColumn('account_status');
        });
    }
};
