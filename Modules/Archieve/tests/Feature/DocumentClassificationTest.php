<?php

use App\Models\User;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Setup Permissions
    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->viewRole = Role::firstOrCreate(['name' => 'Archieve Viewer', 'guard_name' => 'web']);
    $this->viewRole->syncPermissions([ArchieveUserPermission::ViewClassification->value]);

    $this->manageRole = Role::firstOrCreate(['name' => 'Archieve Manager', 'guard_name' => 'web']);
    $this->manageRole->syncPermissions([
        ArchieveUserPermission::ViewClassification->value,
        ArchieveUserPermission::ManageClassification->value,
    ]);
});

describe('Document Classification Access Control', function () {
    it('allows users with lihat_klasifikasi_arsip to access index, datatable, and print', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $this->actingAs($user)->get('/archieve/classifications')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/classifications/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/classifications/print/excel')->assertStatus(200);
    });

    it('denies users without kelola_klasifikasi_arsip from accessing management routes', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $classification = DocumentClassification::factory()->create(['code' => 'A1', 'name' => 'Authored']);

        $this->actingAs($user)->get('/archieve/classifications/create')->assertStatus(403);
        $this->actingAs($user)->post('/archieve/classifications', [])->assertStatus(403);
        $this->actingAs($user)->get("/archieve/classifications/{$classification->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/archieve/classifications/{$classification->id}", [])->assertStatus(403);
        $this->actingAs($user)->delete("/archieve/classifications/{$classification->id}")->assertStatus(403);
    });

    it('allows users with kelola_klasifikasi_arsip to access management routes', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $classification = DocumentClassification::factory()->create(['code' => 'A1', 'name' => 'Authored']);

        $this->actingAs($user)->get('/archieve/classifications/create')->assertStatus(200);
        $this->actingAs($user)->get("/archieve/classifications/{$classification->id}/edit")->assertStatus(200);
    });
});

describe('Document Classification Datatable Features', function () {
    it('can search globally and individually', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        DocumentClassification::factory()->create(['code' => 'ADM', 'name' => 'Administration', 'description' => 'General admin']);
        DocumentClassification::factory()->create(['code' => 'LAW', 'name' => 'Legal', 'description' => 'Law related']);

        // Global search
        $response = $this->actingAs($user)->get('/archieve/classifications/datatable?search=ADM');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.code'))->toBe('ADM');

        // Individual code search
        $response = $this->actingAs($user)->get('/archieve/classifications/datatable?code=LAW');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.code'))->toBe('LAW');

        // Individual name search
        $response = $this->actingAs($user)->get('/archieve/classifications/datatable?name=Admin');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Administration');

        // Individual description search
        $response = $this->actingAs($user)->get('/archieve/classifications/datatable?description=Law');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Legal');
    });

    it('can paginate and limit results', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        DocumentClassification::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/archieve/classifications/datatable?limit=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['current_page'])->toBe(2);
    });
});

describe('Document Classification Print Functionality', function () {
    it('returns xlsx file for printing classifications', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $response = $this->actingAs($user)->get('/archieve/classifications/print/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Klasifikasi Arsip Per ' . date('d F Y') . '.xlsx"');
    });
});

describe('Document Classification Form Validation', function () {
    it('requires name and code when creating', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $response = $this->actingAs($user)->post('/archieve/classifications', [
            'name' => '',
            'code' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'code']);
    });

    it('requires code to be unique', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        DocumentClassification::factory()->create(['code' => 'CORE', 'name' => 'Existing']);

        $response = $this->actingAs($user)->post('/archieve/classifications', [
            'name' => 'New',
            'code' => 'CORE',
        ]);

        $response->assertSessionHasErrors(['code']);
    });
});
