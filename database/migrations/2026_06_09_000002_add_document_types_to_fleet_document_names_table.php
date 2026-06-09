<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_document_names')) {
            return;
        }

        if (! Schema::hasColumn('fleet_document_names', 'document_types')) {
            Schema::table('fleet_document_names', function (Blueprint $table) {
                $table->json('document_types')->nullable()->after('document_type');
            });
        }

        DB::table('fleet_document_names')
            ->select(['id', 'document_type', 'document_types'])
            ->orderBy('id')
            ->get()
            ->each(function ($row): void {
                if (filled($row->document_types)) {
                    return;
                }

                $legacyType = trim((string) ($row->document_type ?: 'All Modules'));
                DB::table('fleet_document_names')
                    ->where('id', $row->id)
                    ->update([
                        'document_types' => json_encode([$legacyType], JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_document_names') && Schema::hasColumn('fleet_document_names', 'document_types')) {
            Schema::table('fleet_document_names', function (Blueprint $table) {
                $table->dropColumn('document_types');
            });
        }
    }
};
