<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_contact_methods')) {
            Schema::create('fleet_contact_methods', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $this->seedFallbackRows('fleet_contact_methods', [
            'Email',
            'Phone',
            'WhatsApp',
            'Other',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_contact_methods');
    }

    private function seedFallbackRows(string $targetTable, array $names): void
    {
        if (! Schema::hasTable($targetTable) || DB::table($targetTable)->exists()) {
            return;
        }

        foreach ($names as $index => $name) {
            DB::table($targetTable)->updateOrInsert(
                ['code' => $this->normalizeCode($name)],
                [
                    'name' => $name,
                    'description' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function normalizeCode(string $value): string
    {
        $code = Str::of($value)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $code !== '' ? $code : 'MASTER_' . Str::upper(Str::random(6));
    }
};
