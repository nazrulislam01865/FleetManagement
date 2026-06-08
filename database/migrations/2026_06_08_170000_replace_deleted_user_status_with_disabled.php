<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'account_status')) {
            return;
        }

        DB::table('users')
            ->where('account_status', 'deleted')
            ->update(['account_status' => 'disabled']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'account_status')) {
            return;
        }

        DB::table('users')
            ->where('account_status', 'disabled')
            ->update(['account_status' => 'deleted']);
    }
};
