<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_document_names') || ! Schema::hasColumn('fleet_document_names', 'document_type')) {
            return;
        }

        $documents = [
            'Employee NID Copy',
            'Employee Appointment Letter',
            'Educational Certificate',
            'Experience Certificate',
            'Police Verification Copy',
        ];

        $nextSort = (int) DB::table('fleet_document_names')->max('sort_order');
        foreach ($documents as $document) {
            $nextSort++;
            DB::table('fleet_document_names')->updateOrInsert(
                ['code' => Str::of($document)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                [
                    'name' => $document,
                    'document_type' => 'Employees',
                    'description' => 'Employee document',
                    'sort_order' => $nextSort,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fleet_document_names')) {
            return;
        }

        DB::table('fleet_document_names')
            ->whereIn('code', [
                'EMPLOYEE_NID_COPY',
                'EMPLOYEE_APPOINTMENT_LETTER',
                'EDUCATIONAL_CERTIFICATE',
                'EXPERIENCE_CERTIFICATE',
                'POLICE_VERIFICATION_COPY',
            ])
            ->delete();
    }
};
