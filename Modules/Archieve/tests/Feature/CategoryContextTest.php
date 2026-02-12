<?php

use App\Models\User;
use Modules\Archieve\Models\CategoryContext;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Setup Permissions
    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->viewRole = Role::firstOrCreate(['name' => 'Archieve Viewer', 'guard_name' => 'web']);
    $this->viewRole->syncPermissions([ArchieveUserPermission::ViewCategory->value]);

    $this->manageRole = Role::firstOrCreate(['name' => 'Archieve Manager', 'guard_name' => 'web']);
    $this->manageRole->syncPermissions([
        ArchieveUserPermission::ViewCategory->value,
        ArchieveUserPermission::ManageCategory->value,
    ]);
});

describe('Category Context Access Control', function () {
    it('allows users with lihat_kategori_arsip to access index, datatable, and print', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $this->actingAs($user)->get('/archieve/contexts')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/contexts/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/contexts/print/excel')->assertStatus(200);
    });

    it('denies users without kelola_kategori_arsip from accessing management routes', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $context = CategoryContext::factory()->create();

        $this->actingAs($user)->get('/archieve/contexts/create')->assertStatus(403);
        $this->actingAs($user)->post('/archieve/contexts', [])->assertStatus(403);
        $this->actingAs($user)->get("/archieve/contexts/{$context->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/archieve/contexts/{$context->id}", [])->assertStatus(403);
        $this->actingAs($user)->delete("/archieve/contexts/{$context->id}")->assertStatus(403);
    });

    it('allows users with kelola_kategori_arsip to access management routes', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $context = CategoryContext::factory()->create();

        $this->actingAs($user)->get('/archieve/contexts/create')->assertStatus(200);
        $this->actingAs($user)->get("/archieve/contexts/{$context->id}/edit")->assertStatus(200);
    });
});

describe('Category Context Datatable Features', function () {
    it('can search globally and individually', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        CategoryContext::factory()->create(['name' => 'Legal Documents', 'description' => 'Plain legal files']);
        CategoryContext::factory()->create(['name' => 'Finance Records', 'description' => 'Invoices and receipts']);

        // Global search
        $response = $this->actingAs($user)->get('/archieve/contexts/datatable?search=Legal');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Legal Documents');

        // Individual name search
        $response = $this->actingAs($user)->get('/archieve/contexts/datatable?name=Finance');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Finance Records');

        // Individual description search
        $response = $this->actingAs($user)->get('/archieve/contexts/datatable?description=Invoice');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Finance Records');
    });

    it('can paginate and limit results', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        CategoryContext::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/archieve/contexts/datatable?limit=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['current_page'])->toBe(2);
    });
});

describe('Category Context Print Functionality', function () {
    it('returns xlsx file for printing contexts', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $response = $this->actingAs($user)->get('/archieve/contexts/print/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Konteks Arsip Per ' . date('d F Y') . '.xlsx"');
    });
});
