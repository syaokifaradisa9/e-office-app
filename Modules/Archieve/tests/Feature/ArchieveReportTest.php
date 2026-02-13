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

    $this->division = Division::factory()->create(['name' => 'Finance Dept']);
    $this->otherDivision = Division::factory()->create(['name' => 'IT Dept']);

    // Roles
    $this->divisionReportRole = Role::firstOrCreate(['name' => 'Division Report Viewer', 'guard_name' => 'web']);
    $this->divisionReportRole->givePermissionTo(ArchieveUserPermission::ViewReportDivision->value);

    $this->globalReportRole = Role::firstOrCreate(['name' => 'Global Report Viewer', 'guard_name' => 'web']);
    $this->globalReportRole->givePermissionTo(ArchieveUserPermission::ViewReportAll->value);
});

describe('Report Access Control', function () {

    it('denies access to division report for users without permission', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        
        $this->actingAs($user)
            ->get('/archieve/reports')
            ->assertStatus(403);
    });

    it('denies access to global report for users without permission', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get('/archieve/reports/all')
            ->assertStatus(403);
    });

    it('denies division report if user has permission but no division', function () {
        $user = User::factory()->create(['division_id' => null]);
        $user->assignRole($this->divisionReportRole);

        $this->actingAs($user)
            ->get('/archieve/reports')
            ->assertStatus(403);
    });

    it('allows access to division report for authorized staff', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionReportRole);

        $this->actingAs($user)
            ->get('/archieve/reports')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archieve/Report/Index')
                ->has('reportData')
            );
    });

    it('allows access to global report for authorized admins', function () {
        $user = User::factory()->create();
        $user->assignRole($this->globalReportRole);

        $this->actingAs($user)
            ->get('/archieve/reports/all')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Archieve/Report/All')
                ->has('reportData')
            );
    });
});

describe('Report Data Accuracy', function () {

    it('provides accurate statistics for division reports', function () {
        $user = User::factory()->create(['division_id' => $this->division->id]);
        $user->assignRole($this->divisionReportRole);

        // Setup storage
        DivisionStorage::factory()->create([
            'division_id' => $this->division->id,
            'max_size' => 500 * 1024 * 1024, // 500MB
        ]);

        // Documents in assigned division
        $docs = Document::factory()->count(5)->create(['file_size' => 1024 * 1024]); // 1MB each
        foreach ($docs as $doc) {
            $doc->divisions()->attach($this->division->id, ['allocated_size' => 1024 * 1024]);
        }

        // Document in other division (should not be counted in this report)
        $otherDoc = Document::factory()->create(['file_size' => 10 * 1024 * 1024]);
        $otherDoc->divisions()->attach($this->otherDivision->id, ['allocated_size' => 10 * 1024 * 1024]);

        $response = $this->actingAs($user)->get('/archieve/reports');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportData.division_name', 'Finance Dept')
            ->where('reportData.overview_stats.total_documents', 5)
            ->where('reportData.overview_stats.total_size', 5 * 1024 * 1024)
            ->where('reportData.overview_stats.storage_percentage', 1)
        );
    });

    it('provides accurate aggregated data for global reports', function () {
        $user = User::factory()->create();
        $user->assignRole($this->globalReportRole);

        // Doc in Div A
        $docA = Document::factory()->create(['file_size' => 2 * 1024 * 1024]);
        $docA->divisions()->attach($this->division->id, ['allocated_size' => 2 * 1024 * 1024]);

        // Doc in Div B
        $docB = Document::factory()->create(['file_size' => 3 * 1024 * 1024]);
        $docB->divisions()->attach($this->otherDivision->id, ['allocated_size' => 3 * 1024 * 1024]);

        $response = $this->actingAs($user)->get('/archieve/reports/all');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportData.global.overview_stats.total_documents', 2)
            ->where('reportData.global.overview_stats.total_size', 5 * 1024 * 1024)
            ->has('reportData.per_division', 2)
        );
    });
});
