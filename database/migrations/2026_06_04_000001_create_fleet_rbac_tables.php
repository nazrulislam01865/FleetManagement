<?php

use App\Support\FleetRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('fleet_roles')) {
            Schema::create('fleet_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fleet_permissions')) {
            Schema::create('fleet_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('module')->index();
                $table->string('action')->default('View');
                $table->string('label');
                $table->text('description')->nullable();
                $table->string('route_name')->nullable()->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fleet_role_permissions')) {
            Schema::create('fleet_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('fleet_roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('fleet_permissions')->cascadeOnDelete();
                $table->boolean('allowed')->default(false);
                $table->timestamps();

                $table->unique(['role_id', 'permission_id']);
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'fleet_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('fleet_role_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('fleet_roles')
                    ->nullOnDelete();
            });
        }

        FleetRbac::syncDefaults(true);
        FleetRbac::assignDefaultRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'fleet_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('fleet_role_id');
                } catch (Throwable) {
                    $table->dropColumn('fleet_role_id');
                }
            });
        }

        Schema::dropIfExists('fleet_role_permissions');
        Schema::dropIfExists('fleet_permissions');
        Schema::dropIfExists('fleet_roles');
    }
};
