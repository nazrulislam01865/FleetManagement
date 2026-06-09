<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fleet_record_owners')) {
            Schema::create('fleet_record_owners', function (Blueprint $table) {
                $table->id();
                $table->string('resource_type', 80);
                $table->string('resource_code', 191);
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['resource_type', 'resource_code']);
                $table->index(['user_id', 'resource_type']);
            });
        }

        if (! Schema::hasTable('fleet_notification_deliveries')) {
            Schema::create('fleet_notification_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('dedupe_key', 191);
                $table->uuid('notification_id')->nullable();
                $table->timestamp('delivered_at');
                $table->timestamps();

                $table->unique(['user_id', 'dedupe_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_notification_deliveries');
        Schema::dropIfExists('fleet_record_owners');
        Schema::dropIfExists('notifications');
    }
};
