<?php

namespace Tests\Feature;

use App\Models\Fleet\FleetDocumentName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_one_document_type_only_deletes_the_selected_database_row(): void
    {
        $first = FleetDocumentName::query()->create($this->documentAttributes(
            code: 'FIRST_DOCUMENT',
            name: 'First Document',
            sortOrder: 1,
        ));
        $selected = FleetDocumentName::query()->create($this->documentAttributes(
            code: 'SELECTED_DOCUMENT',
            name: 'Selected Document',
            sortOrder: 2,
        ));
        $third = FleetDocumentName::query()->create($this->documentAttributes(
            code: 'THIRD_DOCUMENT',
            name: 'Third Document',
            sortOrder: 3,
        ));

        $response = $this
            ->withoutMiddleware()
            ->deleteJson(route('fleet.master-data.document-names.destroy', [
                'documentName' => $selected,
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'deletedId' => $selected->id,
            ]);

        $this->assertDatabaseMissing('fleet_document_names', ['id' => $selected->id]);
        $this->assertDatabaseHas('fleet_document_names', ['id' => $first->id, 'code' => 'FIRST_DOCUMENT']);
        $this->assertDatabaseHas('fleet_document_names', ['id' => $third->id, 'code' => 'THIRD_DOCUMENT']);
        $this->assertDatabaseCount('fleet_document_names', 2);
    }

    public function test_bulk_master_sync_does_not_delete_document_types_missing_from_the_payload(): void
    {
        $document = FleetDocumentName::query()->create($this->documentAttributes(
            code: 'KEEP_THIS_DOCUMENT',
            name: 'Keep This Document',
            sortOrder: 1,
        ));

        $response = $this
            ->withoutMiddleware()
            ->postJson(route('fleet.master-data.sync'), [
                'vehicle_categories' => [],
                'vehicle_sub_categories' => [],
                'party_types' => [],
                'document_names' => [],
                'licence_types' => [],
                'driver_contact_types' => [],
                'client_types' => [],
                'contact_methods' => [],
                'fuel_types' => [],
                'fuel_units' => [],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('fleet_document_names', [
            'id' => $document->id,
            'code' => 'KEEP_THIS_DOCUMENT',
        ]);
        $this->assertDatabaseCount('fleet_document_names', 1);
    }

    private function documentAttributes(string $code, string $name, int $sortOrder): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'document_type' => 'Vehicles',
            'document_types' => ['Vehicles'],
            'description' => null,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ];
    }
}
