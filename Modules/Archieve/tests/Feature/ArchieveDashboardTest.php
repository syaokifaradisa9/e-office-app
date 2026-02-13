<?php

use App\Models\User;
use App\Models\Division;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Setup Permissions
    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->division = Division::factory()->create(['name' => 'IT Department']);
    $this->otherDivision = Division::factory()->create(['name' => 'HR Department']);

    // Create roles for specific testing
    $this->divisionRole = Role::firstOrCreate(['name' => 'Division Dashboard Viewer', 'guard_name' => 'web']);
    $this->divisionRole->givePermissionTo(ArchieveUserPermission::ViewDashboardDivision->value);

    $this->globalRole = Role::firstOrCreate(['name' => 'Global Dashboard Viewer', 'guard_name' => 'web']);
    $this->globalRole->givePermissionTo(ArchieveUserPermission::ViewDashboardAll->value);
});

describe('Dashboard Access Control', function () {

    it('denies access to users without any dashboard permissions', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get('/archieve/dashboard')
            ->assertStatus(403);
    });

    it('only shows division tab for users with division dashboard permission', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionRole);

        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archieve/Dashboard/Index')
            ->has('tabs', 1)
            ->where('tabs.0.id', 'division')
            ->where('tabs.0.type', 'division')
        );
    });

    it('only shows overall tab for users with global dashboard permission', function () {
        $user = User::factory()->create();
        $user->assignRole($this->globalRole);

        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archieve/Dashboard/Index')
            ->has('tabs', 1)
            ->where('tabs.0.id', 'all')
            ->where('tabs.0.type', 'overview')
        );
    });

    it('shows both tabs for users with both permissions', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->givePermissionTo(ArchieveUserPermission::ViewDashboardDivision->value);
        $user->givePermissionTo(ArchieveUserPermission::ViewDashboardAll->value);

        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archieve/Dashboard/Index')
            ->has('tabs', 2)
            ->where('tabs.0.id', 'division')
            ->where('tabs.1.id', 'all')
        );
    });
});

describe('Dashboard Data Accuracy', function () {

    it('calculates division storage usage and document count correctly', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionRole);

        // Setup storage
        $maxSize = 100 * 1024 * 1024; // 100MB
        DivisionStorage::factory()->create([
            'division_id' => $this->division->id,
            'max_size' => $maxSize,
        ]);

        // Create documents for this division
        $docSize = 5 * 1024 * 1024; // 5MB each
        $docs = Document::factory()->count(3)->create([
            'file_size' => $docSize,
        ]);
        
        foreach ($docs as $doc) {
            $doc->divisions()->attach($this->division->id, ['allocated_size' => $docSize]);
        }

        // Update division storage used_size (usually handled by service, simulating here if needed or relying on DocumentRepository sum)
        // Note: ArchieveDashboardService uses DocumentRepository::sumSizeByDivision
        
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tabs.0.data.document_count', 3)
            ->where('tabs.0.data.storage.used', 15 * 1024 * 1024)
            ->where('tabs.0.data.storage.max', $maxSize)
            ->where('tabs.0.data.storage.percentage', 15)
        );
    });

    it('aggregates system-wide data correctly in the overall tab', function () {
        $user = User::factory()->create();
        $user->assignRole($this->globalRole);

        // Doc in Div 1
        $doc1 = Document::factory()->create(['file_size' => 10 * 1024 * 1024]);
        $doc1->divisions()->attach($this->division->id, ['allocated_size' => 10 * 1024 * 1024]);

        // Doc in Div 2
        $doc2 = Document::factory()->create(['file_size' => 20 * 1024 * 1024]);
        $doc2->divisions()->attach($this->otherDivision->id, ['allocated_size' => 20 * 1024 * 1024]);

        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tabs.0.data.total_documents', 2)
            ->where('tabs.0.data.total_size', 30 * 1024 * 1024)
        );
    });
});

describe('Dashboard Edge Cases', function () {

    it('does not show division tab if user has permission but no division assigned', function () {
        $user = User::factory()->create(['division_id' => null]);
        $user->assignRole($this->divisionRole);

        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('tabs', 0)
        );
    });

    it('handles division with zero or null storage correctly', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionRole);

        // No DivisionStorage record created
        
        $response = $this->actingAs($user)->get('/archieve/dashboard');
        
        $response->assertInertia(fn (Assert $page) => $page
            ->where('tabs.0.data.storage.max', 0)
            ->where('tabs.0.data.storage.percentage', 0)
        );
    });
});
