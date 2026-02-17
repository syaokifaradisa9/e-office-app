<?php

use App\Models\User;
use Modules\Archieve\Models\Category;
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

    $this->context = CategoryContext::factory()->create(['name' => 'General']);
});

describe('Category Access Control', function () {
    /**
     * Memastikan user dengan izin 'lihat_kategori_arsip' dapat mengakses index, datatable, dan fitur print.
     */
    it('allows users with view_kategori_arsip to access index, datatable, and print', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $this->actingAs($user)->get('/archieve/categories')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/categories/datatable')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/categories/print/excel')->assertStatus(200);
    });

    /**
     * Memastikan user tanpa izin 'kelola_kategori_arsip' dilarang mengakses route manajemen (create, edit, delete).
     */
    it('denies users without manage_kategori_arsip from accessing management routes', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $category = Category::factory()->create(['context_id' => $this->context->id]);

        $this->actingAs($user)->get('/archieve/categories/create')->assertStatus(403);
        $this->actingAs($user)->post('/archieve/categories', [])->assertStatus(403);
        $this->actingAs($user)->get("/archieve/categories/{$category->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/archieve/categories/{$category->id}", [])->assertStatus(403);
        $this->actingAs($user)->delete("/archieve/categories/{$category->id}")->assertStatus(403);
    });

    /**
     * Memastikan user dengan izin 'kelola_kategori_arsip' diperbolehkan mengakses route manajemen.
     */
    it('allows users with manage_kategori_arsip to access management routes', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $category = Category::factory()->create(['context_id' => $this->context->id]);

        $this->actingAs($user)->get('/archieve/categories/create')->assertStatus(200);
        $this->actingAs($user)->get("/archieve/categories/{$category->id}/edit")->assertStatus(200);
    });
});

describe('Category Datatable Features', function () {
    /**
     * Menguji fitur pencarian datatable baik secara global maupun pada kolom spesifik (nama/konteks/deskripsi).
     */
    it('can search globally and individually', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $otherContext = CategoryContext::factory()->create(['name' => 'Secret']);

        Category::factory()->create([
            'name' => 'Public Contract', 
            'context_id' => $this->context->id,
            'description' => 'Visible to all'
        ]);
        Category::factory()->create([
            'name' => 'Private Agreement', 
            'context_id' => $otherContext->id,
            'description' => 'Restricted access'
        ]);

        // Global search
        $response = $this->actingAs($user)->get('/archieve/categories/datatable?search=Public');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Public Contract');

        // Individual name search
        $response = $this->actingAs($user)->get('/archieve/categories/datatable?name=Private');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Private Agreement');

        // Individual context search
        $response = $this->actingAs($user)->get('/archieve/categories/datatable?context_id=' . $this->context->id);
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Public Contract');

        // Individual description search
        $response = $this->actingAs($user)->get('/archieve/categories/datatable?description=Restricted');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Private Agreement');
    });

    /**
     * Memastikan fitur pagination dan limitasi jumlah data pada datatable bekerja dengan benar.
     */
    it('can paginate and limit results', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        Category::factory()->count(15)->create(['context_id' => $this->context->id]);

        $response = $this->actingAs($user)->get('/archieve/categories/datatable?limit=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data'])->toHaveCount(5);
        expect($json['current_page'])->toBe(2);
    });
});

describe('Category Print Functionality', function () {
    /**
     * Memastikan fitur cetak (print) menghasilkan file excel (.xlsx) dengan header yang sesuai.
     */
    it('returns xlsx file for printing categories', function () {
        $user = User::factory()->create();
        $user->assignRole($this->viewRole);

        $response = $this->actingAs($user)->get('/archieve/categories/print/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="Data Kategori Arsip Per ' . date('d F Y') . '.xlsx"');
    });
});

describe('Category Form Validation', function () {
    /**
     * Memastikan field 'nama' dan 'ID konteks' wajib diisi saat membuat kategori baru.
     */
    it('requires name and context_id when creating', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $response = $this->actingAs($user)->post('/archieve/categories', [
            'name' => '',
            'context_id' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'context_id']);
    });

    /**
     * Memastikan 'ID konteks' yang diberikan harus benar-benar terdaftar di database saat pembuatan kategori.
     */
    it('requires context_id to exist when creating', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $response = $this->actingAs($user)->post('/archieve/categories', [
            'name' => 'Test Category',
            'context_id' => 9999,
        ]);

        $response->assertSessionHasErrors(['context_id']);
    });

    /**
     * Memastikan validasi yang sama (nama & ID konteks) diterapkan saat melakukan pembaruan data kategori.
     */
    it('validates name and context_id when updating', function () {
        $user = User::factory()->create();
        $user->assignRole($this->manageRole);

        $category = Category::factory()->create(['context_id' => $this->context->id]);

        $response = $this->actingAs($user)->put("/archieve/categories/{$category->id}", [
            'name' => '',
            'context_id' => 9999,
        ]);

        $response->assertSessionHasErrors(['name', 'context_id']);
    });
});
