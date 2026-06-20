<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_releases')) {
            return;
        }

        if (! Schema::hasColumn('fleet_releases', 'issue_type')) {
            Schema::table('fleet_releases', function (Blueprint $table): void {
                $table->string('issue_type', 80)->nullable()->index();
            });
        }

        if (! Schema::hasColumn('fleet_releases', 'initiated_by_user_id')) {
            Schema::table('fleet_releases', function (Blueprint $table): void {
                $table->foreignId('initiated_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fleet_releases')) {
            return;
        }

        if (Schema::hasColumn('fleet_releases', 'initiated_by_user_id')) {
            Schema::table('fleet_releases', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('initiated_by_user_id');
            });
        }

        if (Schema::hasColumn('fleet_releases', 'issue_type')) {
            Schema::table('fleet_releases', function (Blueprint $table): void {
                $table->dropColumn('issue_type');
            });
        }
    }
};
