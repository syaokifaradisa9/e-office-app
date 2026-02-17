<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Models\VisitorPurpose;
use App\Models\Division;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;


beforeEach(function () {
    foreach (VisitorUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

describe('Visitor Management Reports', function () {
    test('user with ViewReport permission can access report index', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewReport->value);

        $this->actingAs($user)->get('/visitor/reports')->assertOk();
    });

    test('user without ViewReport permission cannot access report index', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/visitor/reports')->assertForbidden();
    });

    test('user with ViewReport permission can export comprehensive excel', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewReport->value);

        // Seed some data
        $division = Division::factory()->create();
        $purpose = VisitorPurpose::factory()->create();
        
        Visitor::factory()->count(5)->create([
            'division_id' => $division->id,
            'purpose_id' => $purpose->id,
            'check_in_at' => now(),
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)->get('/visitor/reports/export');
        $response->assertOk();
        
        $contentDisposition = $response->headers->get('content-disposition');
        expect($contentDisposition)->toContain('Laporan_Komprehensif_Pengunjung');
        expect($contentDisposition)->toContain('.xlsx');
    });

    test('user without ViewReport permission cannot export excel', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/visitor/reports/export')->assertForbidden();
    });
});
