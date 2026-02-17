<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary permissions
    foreach (VisitorUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

// ============================================================================
// 1. Access Control
// ============================================================================

describe('Access Control', function () {
    test('user with ViewMaster permission can access index, datatable, and excel export', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewMaster->value);

        $this->actingAs($user)->get('/visitor/purposes')->assertOk();
        $this->actingAs($user)->get('/visitor/purposes/datatable')->assertOk();
        $this->actingAs($user)->get('/visitor/purposes/print/excel')->assertOk();
    });

    test('user with ManageMaster permission can access CRUD operations', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ManageMaster->value);
        $purpose = VisitorPurpose::factory()->create();

        $this->actingAs($user)->get('/visitor/purposes/create')->assertOk();
        $this->actingAs($user)->post('/visitor/purposes/store', [
            'name' => 'Test Purpose',
            'description' => 'Test Description',
            'is_active' => true
        ])->assertRedirect(route('visitor.purposes.index'));

        $this->actingAs($user)->get("/visitor/purposes/{$purpose->id}/edit")->assertOk();
        $this->actingAs($user)->put("/visitor/purposes/{$purpose->id}/update", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => false
        ])->assertRedirect(route('visitor.purposes.index'));

        $this->actingAs($user)->delete("/visitor/purposes/{$purpose->id}/delete")->assertRedirect();
    });

    test('user without permission cannot access purpose routes', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/visitor/purposes')->assertForbidden();
        $this->actingAs($user)->get('/visitor/purposes/datatable')->assertForbidden();
        $this->actingAs($user)->get('/visitor/purposes/create')->assertForbidden();
        $this->actingAs($user)->post('/visitor/purposes/store')->assertForbidden();
        $this->actingAs($user)->get('/visitor/purposes/print/excel')->assertForbidden();
    });
});

// ============================================================================
// 2. Form Validation
// ============================================================================

describe('Form Validation', function () {
    test('validation rules on create', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ManageMaster->value);

        // Name is required
        $this->actingAs($user)->post('/visitor/purposes/store', [
            'name' => '',
        ])->assertSessionHasErrors(['name']);

        // Name must be unique
        VisitorPurpose::factory()->create(['name' => 'Existing Purpose']);
        $this->actingAs($user)->post('/visitor/purposes/store', [
            'name' => 'Existing Purpose',
        ])->assertSessionHasErrors(['name']);
    });

    test('validation rules on update', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ManageMaster->value);
        $purpose = VisitorPurpose::factory()->create(['name' => 'Original Name']);
        $otherPurpose = VisitorPurpose::factory()->create(['name' => 'Other Name']);

        // Name must be unique except for current item
        $this->actingAs($user)->put("/visitor/purposes/{$purpose->id}/update", [
            'name' => 'Other Name',
        ])->assertSessionHasErrors(['name']);

        $this->actingAs($user)->put("/visitor/purposes/{$purpose->id}/update", [
            'name' => 'Original Name', // Same as current is fine
        ])->assertSessionHasNoErrors();
    });
});

// ============================================================================
// 3. Datatable Features
// ============================================================================

describe('Datatable Features', function () {
    test('handles search, limit, pagination and sort successfully', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewMaster->value);

        // Create multiple purposes
        VisitorPurpose::factory()->create(['name' => 'Apple', 'description' => 'Red fruit']);
        VisitorPurpose::factory()->create(['name' => 'Banana', 'description' => 'Yellow fruit']);
        VisitorPurpose::factory()->create(['name' => 'Cherry', 'description' => 'Small red fruit']);
        
        // Search
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?search=Apple');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Apple');

        // Global Search handles description too
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?search=Yellow');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Banana');

        // Limit
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?limit=1');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('per_page', 1);

        // Sort
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?sort_by=name&sort_direction=desc');
        $response->assertJsonPath('data.0.name', 'Cherry');
        
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?sort_by=name&sort_direction=asc');
        $response->assertJsonPath('data.0.name', 'Apple');
    });

    test('handles footer column search correctly', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewMaster->value);

        VisitorPurpose::factory()->create(['name' => 'Specific Name', 'description' => 'Common Description']);
        VisitorPurpose::factory()->create(['name' => 'Other Name', 'description' => 'Specific Description']);

        // Search by name column
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?name=Specific');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Specific Name');

        // Search by description column
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?description=Specific');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.description', 'Specific Description');

        // Search by status column (active)
        VisitorPurpose::factory()->create(['name' => 'Active Item', 'is_active' => true]);
        VisitorPurpose::factory()->create(['name' => 'Inactive Item', 'is_active' => false]);
        
        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?status=active');
        $response->assertJsonFragment(['name' => 'Active Item']);
        $response->assertJsonMissing(['name' => 'Inactive Item']);

        $response = $this->actingAs($user)->get('/visitor/purposes/datatable?status=inactive');
        $response->assertJsonFragment(['name' => 'Inactive Item']);
        $response->assertJsonMissing(['name' => 'Active Item']);
    });
});

// ============================================================================
// 4. Excel Export
// ============================================================================

describe('Excel Export', function () {
    test('generates xlsx file', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewMaster->value);

        VisitorPurpose::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/visitor/purposes/print/excel');
        $response->assertOk();
        
        $contentDisposition = $response->headers->get('content-disposition');
        expect($contentDisposition)->toContain('.xlsx');
    });
});
