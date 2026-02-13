<?php

use App\Models\User;
use App\Models\Division;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Archieve\Models\Document;
use Modules\Archieve\Models\Category;
use Modules\Archieve\Models\DocumentClassification;
use Modules\Archieve\Models\DivisionStorage;
use Modules\Archieve\Enums\ArchieveUserPermission;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Reset permissions cache
    $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    // Create required permissions
    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Storage::fake('public');
});

function createUserWithPermissions(array $permissions, ?int $divisionId = null): User
{
    $user = User::factory()->create([
        'division_id' => $divisionId ?? Division::factory()->create()->id
    ]);
    $user->givePermissionTo($permissions);
    return $user;
}

describe('Archieve Document Access Control (RBAC)', function () {
    /**
     * Memastikan user dengan izin kelola divisi dapat mengakses route dokumen.
     */
    it('allows users with manage permissions to access document routes', function () {
        // 1. Persiapan user dengan izin kelola dan lihat divisi
        $user = createUserWithPermissions([
            ArchieveUserPermission::ManageDivision->value,
            ArchieveUserPermission::ViewDivision->value
        ]);

        // 2. Akses index, datatable, dan create page
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=division')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/create')->assertStatus(200);
    });

    /**
     * Memastikan guest (tamu) diarahkan ke login.
     */
    it('denies unauthenticated users from accessing documents', function () {
        $this->get('/archieve/documents')->assertRedirect('/login');
        $this->post('/archieve/documents', [])->assertRedirect('/login');
    });

    /**
     * Memastikan user tanpa izin apapun mendapatkan error 403.
     */
    it('returns 403 for users without any archieve permissions', function () {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(403);
    });

    /**
     * Memastikan sistem tidak crash bagi user yang tidak terikat ke divisi manapun.
     */
    it('does not crash for users without a division', function () {
        $user = createUserWithPermissions([ArchieveUserPermission::ViewDivision->value], null);
        // Force division_id to null explicitly if factory sets it
        $user->update(['division_id' => null]);
        
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
    });
});

describe('Archieve Document Data Scoping', function () {
    /**
     * Memastikan user dengan izin 'kelola_arsip_divisi' hanya melihat dokumen divisinya sendiri.
     */
    it('scopes datatable to division for users with manage_arsip_divisi', function () {
        // 1. Buat 2 divisi berbeda
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();
        
        // 2. User A di Divisi A
        $userA = createUserWithPermissions([
            ArchieveUserPermission::ManageDivision->value,
            ArchieveUserPermission::ViewDivision->value
        ], $divA->id);

        // 3. Buat dokumen di masing-masing divisi
        $docA = Document::factory()->create(['title' => 'Doc Division A']);
        $docA->divisions()->attach($divA->id, ['allocated_size' => 100]);

        $docB = Document::factory()->create(['title' => 'Doc Division B']);
        $docB->divisions()->attach($divB->id, ['allocated_size' => 100]);

        // 4. Request datatable sebagai User A (view_type=division)
        $response = $this->actingAs($userA)
            ->get('/archieve/documents/datatable?view_type=division');

        // 5. Validasi: Hanya Doc A yang muncul
        $response->assertJsonFragment(['title' => 'Doc Division A']);
        $response->assertJsonMissing(['title' => 'Doc Division B']);
    });

    /**
     * Memastikan user dengan izin 'kelola_semua_arsip' dapat melihat semua data global.
     */
    it('shows all data for users with manage_semua_arsip permission', function () {
        $user = createUserWithPermissions([
            ArchieveUserPermission::ManageAll->value,
            ArchieveUserPermission::ViewAll->value
        ]);

        Document::factory()->create(['title' => 'Global Doc A']);
        Document::factory()->create(['title' => 'Global Doc B']);

        $response = $this->actingAs($user)
            ->get('/archieve/documents/datatable?view_type=all');

        $response->assertJsonFragment(['title' => 'Global Doc A']);
        $response->assertJsonFragment(['title' => 'Global Doc B']);
    });

    /**
     * Memastikan arsip pribadi terisolasi antar user (tidak bisa melihat punya orang lain).
     */
    it('isolates personal archives between users', function () {
        $userA = createUserWithPermissions([ArchieveUserPermission::ViewPersonal->value]);
        $userB = createUserWithPermissions([ArchieveUserPermission::ViewPersonal->value]);

        $docA = Document::factory()->create(['title' => 'Secret A']);
        $docA->users()->attach($userA->id);

        $docB = Document::factory()->create(['title' => 'Secret B']);
        $docB->users()->attach($userB->id);

        $response = $this->actingAs($userA)
            ->get('/archieve/documents/datatable?view_type=personal');

        $response->assertJsonFragment(['title' => 'Secret A']);
        $response->assertJsonMissing(['title' => 'Secret B']);
    });
});

describe('Archieve Document CRUD & Storage Integrity', function () {
    /**
     * Memastikan penyimpanan dokumen, upload file, dan kalkulasi storage proporsional antar divisi bekerja.
     */
    it('stores document, saves file, and calculates storage proportionally', function () {
        // 1. Persiapan divisi dan storage
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();
        
        DivisionStorage::factory()->create(['division_id' => $divA->id, 'used_size' => 0]);
        DivisionStorage::factory()->create(['division_id' => $divB->id, 'used_size' => 0]);

        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value]);
        $file = UploadedFile::fake()->create('test_doc.pdf', 1); // 1 KB = 1024 bytes

        $data = [
            'title' => 'Test Storage Logic',
            'description' => 'Test Description',
            'classification_id' => DocumentClassification::factory()->create()->id,
            'category_ids' => [Category::factory()->create()->id],
            'division_ids' => [$divA->id, $divB->id], // Dokumen dishare ke 2 divisi
            'file' => $file
        ];

        // 2. Aksi simpan dokumen
        $this->actingAs($user)->post('/archieve/documents', $data)->assertRedirect();

        // 3. Validasi DB dan file storage
        $this->assertDatabaseHas('archieve_documents', ['title' => 'Test Storage Logic', 'file_size' => 1024]);
        
        $document = Document::where('title', 'Test Storage Logic')->first();
        Storage::disk('public')->assertExists($document->file_path);

        // 4. Cek Alokasi Proporsional (1024 bytes / 2 divisi = 512 bytes per divisi)
        $this->assertDatabaseHas('archieve_document_division', [
            'document_id' => $document->id,
            'division_id' => $divA->id,
            'allocated_size' => 512
        ]);

        expect(DivisionStorage::where('division_id', $divA->id)->first()->used_size)->toBe(512);
        expect(DivisionStorage::where('division_id', $divB->id)->first()->used_size)->toBe(512);
    });

    /**
     * Memastikan kuota storage dihitung ulang dengan benar saat update dengan file baru.
     */
    it('recalculates storage correctly on update with new file', function () {
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();
        $divC = Division::factory()->create();

        DivisionStorage::factory()->create(['division_id' => $divA->id, 'used_size' => 512]);
        DivisionStorage::factory()->create(['division_id' => $divB->id, 'used_size' => 512]);
        DivisionStorage::factory()->create(['division_id' => $divC->id, 'used_size' => 0]);

        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value]);
        
        $doc = Document::factory()->create(['file_size' => 1024, 'file_path' => 'old.pdf']);
        $doc->divisions()->attach([
            $divA->id => ['allocated_size' => 512],
            $divB->id => ['allocated_size' => 512]
        ]);
        
        $newFile = UploadedFile::fake()->create('new.pdf', 1); // 1024 bytes
        $updateData = [
            'title' => 'Updated Doc',
            'classification_id' => $doc->classification_id,
            'category_ids' => [Category::factory()->create()->id],
            'division_ids' => [$divA->id, $divC->id],
            'file' => $newFile
        ];

        $this->actingAs($user)->put("/archieve/documents/{$doc->id}", $updateData)->assertRedirect();

        // 1024 / 2 = 512
        expect(DivisionStorage::where('division_id', $divA->id)->first()->used_size)->toBe(512);
        expect(DivisionStorage::where('division_id', $divB->id)->first()->used_size)->toBe(0);
        expect(DivisionStorage::where('division_id', $divC->id)->first()->used_size)->toBe(512);
    });

    /**
     * Memastikan kuota dilepas dan file dihapus saat dokumen didelete.
     */
    it('releases storage and deletes file on document deletion', function () {
        $div = Division::factory()->create();
        $storage = DivisionStorage::factory()->create(['division_id' => $div->id, 'used_size' => 1000]);
        
        $doc = Document::factory()->create(['file_size' => 1000]);
        $doc->divisions()->attach($div->id, ['allocated_size' => 1000]);
        Storage::disk('public')->put($doc->file_path, 'dummy');

        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value]);

        $this->actingAs($user)->delete("/archieve/documents/{$doc->id}")->assertRedirect();

        $this->assertDatabaseMissing('archieve_documents', ['id' => $doc->id]);
        Storage::disk('public')->assertMissing($doc->file_path);
        expect($storage->fresh()->used_size)->toBe(0);
    });
});

describe('Archieve Document Advanced Scenarios', function () {
    /**
     * Memastikan pencegahan upload jika melebihi kuota storage divisi.
     */
    it('prevents upload if it exceeds division storage limit', function () {
        $div = Division::factory()->create();
        DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 1000, 
            'used_size' => 900
        ]);

        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value]);
        $file = UploadedFile::fake()->create('heavy.pdf', 1); // 1KB = 1024B, akan melebihi sisa kuota (100B)

        $data = [
            'title' => 'Too Heavy',
            'classification_id' => DocumentClassification::factory()->create()->id,
            'category_ids' => [Category::factory()->create()->id],
            'division_ids' => [$div->id],
            'file' => $file
        ];

        // Membutuhkan validasi storage limit di Request atau Controller
        $this->actingAs($user)->post('/archieve/documents', $data)
            ->assertSessionHasErrors(['file']);
    });

    /**
     * Test fitur pencarian datatable dengan filter global dan filter kolom individu.
     */
    it('can search datatable with global and column filters', function () {
        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value, ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'SearchMe Alpha']);
        Document::factory()->create(['title' => 'SearchMe Beta']);

        // 1. Pencarian global
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&search=Alpha');
        $response->assertJsonFragment(['title' => 'SearchMe Alpha']);
        $response->assertJsonMissing(['title' => 'SearchMe Beta']);

        // 2. Pencarian per kolom (title)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&title=Beta');
        $response->assertJsonFragment(['title' => 'SearchMe Beta']);
        $response->assertJsonMissing(['title' => 'SearchMe Alpha']);
    });
});
