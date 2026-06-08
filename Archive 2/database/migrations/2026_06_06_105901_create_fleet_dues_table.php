<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fleet_dues', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->index();
            $table->string('party_type')->nullable()->index();
            $table->string('party_id')->nullable()->index();
            $table->string('source_type')->nullable()->index();
            $table->string('source_id')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('Pending')->index();
            $table->date('due_date')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_dues');
    }
};
