<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'profile_photo_path')) {
            $afterColumn = Schema::hasColumn('users', 'account_status') ? 'account_status' : 'remember_token';

            Schema::table('users', function (Blueprint $table) use ($afterColumn): void {
                $table->string('profile_photo_path')->nullable()->after($afterColumn);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'profile_photo_path')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('profile_photo_path');
            });
        }
    }
};
