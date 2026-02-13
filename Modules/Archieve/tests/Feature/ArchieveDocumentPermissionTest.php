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
    it('allows users with manage permissions to access document routes', function () {
        $user = createUserWithPermissions([
            ArchieveUserPermission::ManageDivision->value,
            ArchieveUserPermission::ViewDivision->value
        ]);

        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=division')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/create')->assertStatus(200);
    });

    it('denies unauthenticated users from accessing documents', function () {
        $this->get('/archieve/documents')->assertRedirect('/login');
        $this->post('/archieve/documents', [])->assertRedirect('/login');
    });

    it('returns 403 for users without any archieve permissions', function () {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(403);
    });

    it('does not crash for users without a division', function () {
        $user = createUserWithPermissions([ArchieveUserPermission::ViewDivision->value], null);
        // Force division_id to null explicitly if factory sets it
        $user->update(['division_id' => null]);
        
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
    });
});

describe('Archieve Document Data Scoping', function () {
    it('scopes datatable to division for users with manage_arsip_divisi', function () {
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();
        
        $userA = createUserWithPermissions([
            ArchieveUserPermission::ManageDivision->value,
            ArchieveUserPermission::ViewDivision->value
        ], $divA->id);

        $docA = Document::factory()->create(['title' => 'Doc Division A']);
        $docA->divisions()->attach($divA->id, ['allocated_size' => 100]);

        $docB = Document::factory()->create(['title' => 'Doc Division B']);
        $docB->divisions()->attach($divB->id, ['allocated_size' => 100]);

        $response = $this->actingAs($userA)
            ->get('/archieve/documents/datatable?view_type=division');

        $response->assertJsonFragment(['title' => 'Doc Division A']);
        $response->assertJsonMissing(['title' => 'Doc Division B']);
    });

    it('shows all data for users with manage_semua_arsip', function () {
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
    it('stores document, saves file, and calculates storage proportionally', function () {
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
            'division_ids' => [$divA->id, $divB->id],
            'file' => $file
        ];

        $this->actingAs($user)->post('/archieve/documents', $data)->assertRedirect();

        $this->assertDatabaseHas('archieve_documents', ['title' => 'Test Storage Logic', 'file_size' => 1024]);
        
        $document = Document::where('title', 'Test Storage Logic')->first();
        Storage::disk('public')->assertExists($document->file_path);

        // Check Proportional Allocation (1024 / 2 = 512)
        $this->assertDatabaseHas('archieve_document_division', [
            'document_id' => $document->id,
            'division_id' => $divA->id,
            'allocated_size' => 512
        ]);

        expect(DivisionStorage::where('division_id', $divA->id)->first()->used_size)->toBe(512);
        expect(DivisionStorage::where('division_id', $divB->id)->first()->used_size)->toBe(512);
    });

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
    it('prevents upload if it exceeds division storage limit', function () {
        $div = Division::factory()->create();
        DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 1000, 
            'used_size' => 900
        ]);

        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value]);
        $file = UploadedFile::fake()->create('heavy.pdf', 1); // 1KB = 1024B, will exceed 1000B

        $data = [
            'title' => 'Too Heavy',
            'classification_id' => DocumentClassification::factory()->create()->id,
            'category_ids' => [Category::factory()->create()->id],
            'division_ids' => [$div->id],
            'file' => $file
        ];

        // This requires storage limit validation in the Request or Controller
        $this->actingAs($user)->post('/archieve/documents', $data)
            ->assertSessionHasErrors(['file']);
    });

    it('can search datatable with global and column filters', function () {
        $user = createUserWithPermissions([ArchieveUserPermission::ManageAll->value, ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'SearchMe Alpha']);
        Document::factory()->create(['title' => 'SearchMe Beta']);

        // Global search
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&search=Alpha');
        $response->assertJsonFragment(['title' => 'SearchMe Alpha']);
        $response->assertJsonMissing(['title' => 'SearchMe Beta']);

        // Column search (title)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&title=Beta');
        $response->assertJsonFragment(['title' => 'SearchMe Beta']);
        $response->assertJsonMissing(['title' => 'SearchMe Alpha']);
    });
});
