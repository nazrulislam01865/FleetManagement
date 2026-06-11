<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fleet_releases')) {
            return;
        }

        Schema::create('fleet_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version', 60)->unique();
            $table->string('title');
            $table->date('release_date')->index();
            $table->string('environment', 30)->default('production')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->longText('changes')->nullable();
            $table->longText('known_issues')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_releases');
    }
};
