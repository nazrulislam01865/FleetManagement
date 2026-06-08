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

        if (! Schema::hasColumn('fleet_document_names', 'document_type')) {
            Schema::table('fleet_document_names', function (Blueprint $table) {
                $table->string('document_type')->default('All Modules')->index();
            });
        }

        $this->assignType([
            'Tax Token',
            'Fitness Certificate',
            'Route Permit',
        ], 'Vehicles');

        $this->assignType([
            'Trade License Copy',
            'Vendor Agreement',
            'NID Copy of Owner',
            'Fuel Supply Agreement',
            'Insurance Document',
        ], 'Vendors & Parties');

        $this->assignType([
            'NID Scan Copy',
            'Driving License Copy',
            'Police Verification',
            'Medical Fitness Certificate',
            'Appointment Letter',
            'Training Certificate',
        ], 'Drivers');
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_document_names') && Schema::hasColumn('fleet_document_names', 'document_type')) {
            Schema::table('fleet_document_names', function (Blueprint $table) {
                $table->dropColumn('document_type');
            });
        }
    }

    private function assignType(array $names, string $type): void
    {
        DB::table('fleet_document_names')
            ->whereIn('name', $names)
            ->update(['document_type' => $type, 'updated_at' => now()]);
    }
};
