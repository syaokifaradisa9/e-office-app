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
    $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (ArchieveUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Storage::fake('public');
});

describe('Archieve Digital Search', function () {
    it('allows search access and performs search', function () {
        $user = createDigitalUser([ArchieveUserPermission::SearchDocument->value, ArchieveUserPermission::SearchAllScope->value]);
        
        $doc = Document::factory()->create(['title' => 'FindMe']);
        
        $this->actingAs($user)->get('/archieve/documents/search')->assertStatus(200);
        
        $response = $this->actingAs($user)->get('/archieve/documents/search/results?search=FindMe');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
    });

    it('allows access to search for users with only SearchDivisionScope', function () {
        $user = createDigitalUser([ArchieveUserPermission::SearchDivisionScope->value]);
        $this->actingAs($user)->get('/archieve/documents/search')->assertStatus(200);
    });

    it('allows access to search for users with only SearchPersonalScope', function () {
        $user = createDigitalUser([ArchieveUserPermission::SearchPersonalScope->value]);
        $this->actingAs($user)->get('/archieve/documents/search')->assertStatus(200);
    });

    it('scopes search to specific division for SearchDivisionScope', function () {
        $div1 = Division::factory()->create(['name' => 'Divisi 1']);
        $div2 = Division::factory()->create(['name' => 'Divisi 2']);
        $user = createDigitalUser([ArchieveUserPermission::SearchDocument->value, ArchieveUserPermission::SearchDivisionScope->value], $div1->id);

        $doc1 = Document::factory()->create(['title' => 'Doc Divisi 1']);
        $doc1->divisions()->attach($div1->id, ['allocated_size' => 0]);

        $doc2 = Document::factory()->create(['title' => 'Doc Divisi 2']);
        $doc2->divisions()->attach($div2->id, ['allocated_size' => 0]);

        $response = $this->actingAs($user)->get('/archieve/documents/search/results');
        $response->assertStatus(200);
        
        $titles = collect($response->json('data'))->pluck('title');
        expect($titles)->toContain('Doc Divisi 1');
        expect($titles)->not->toContain('Doc Divisi 2');
    });

    it('shows all documents for SearchAllScope regardless of division', function () {
        $div1 = Division::factory()->create();
        $user = createDigitalUser([ArchieveUserPermission::SearchDocument->value, ArchieveUserPermission::SearchAllScope->value]);

        Document::factory()->create(['title' => 'Doc A']);
        $docB = Document::factory()->create(['title' => 'Doc B']);
        $docB->divisions()->attach($div1->id, ['allocated_size' => 0]);

        $response = $this->actingAs($user)->get('/archieve/documents/search/results');
        expect($response->json('data'))->toHaveCount(2);
    });

    it('scopes search to personal shared documents for SearchPersonalScope', function () {
        $userA = createDigitalUser([ArchieveUserPermission::SearchDocument->value, ArchieveUserPermission::SearchPersonalScope->value]);
        $userB = User::factory()->create();

        $myDoc = Document::factory()->create(['title' => 'Shared with Me']);
        $myDoc->users()->attach($userA->id);

        Document::factory()->create(['title' => 'Not Shared with Me']);

        $response = $this->actingAs($userA)->get('/archieve/documents/search/results');
        $titles = collect($response->json('data'))->pluck('title');
        
        expect($titles)->toContain('Shared with Me');
        expect($titles)->not->toContain('Not Shared with Me');
    });
});

function createDigitalUser(array $permissions, ?int $divisionId = null): User
{
    $user = User::factory()->create([
        'division_id' => $divisionId ?? Division::factory()->create()->id
    ]);
    foreach ($permissions as $permission) {
        $user->givePermissionTo($permission);
    }
    return $user;
}

describe('Archieve Digital Access Control (RBAC)', function () {
    it('allows users with ViewPersonal to access document routes', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewPersonal->value]);
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=personal')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=personal')->assertStatus(200);
    });

    it('allows users with ViewDivision to access document routes', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewDivision->value]);
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=division')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=division')->assertStatus(200);
    });

    it('allows users with ViewAll to access document routes', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=all')->assertStatus(200);
    });
});

describe('Archieve Digital Data Scoping', function () {
    it('scopes datatable to personal user', function () {
        $userA = createDigitalUser([ArchieveUserPermission::ViewPersonal->value]);
        $userB = createDigitalUser([ArchieveUserPermission::ViewPersonal->value]);

        $docA = Document::factory()->create(['title' => 'Personal Doc A']);
        $docA->users()->attach($userA->id);

        $docB = Document::factory()->create(['title' => 'Personal Doc B']);
        $docB->users()->attach($userB->id);

        $response = $this->actingAs($userA)->get('/archieve/documents/datatable?view_type=personal');
        $data = $response->json('data');

        expect($data)->toHaveCount(1);
        expect($data[0]['title'])->toBe('Personal Doc A');
    });

    it('scopes datatable to division', function () {
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();
        
        $userA = createDigitalUser([ArchieveUserPermission::ViewDivision->value], $divA->id);
        
        $docA = Document::factory()->create(['title' => 'Division Doc A']);
        $docA->divisions()->attach($divA->id, ['allocated_size' => 100]);

        $docB = Document::factory()->create(['title' => 'Division Doc B']);
        $docB->divisions()->attach($divB->id, ['allocated_size' => 100]);

        $response = $this->actingAs($userA)->get('/archieve/documents/datatable?view_type=division');
        $data = $response->json('data');

        expect($data)->toHaveCount(1);
        expect($data[0]['title'])->toBe('Division Doc A');
    });

    it('shows all data for ViewAll permission', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all');
        $data = $response->json('data');

        expect($data)->toHaveCount(3);
    });
});

describe('Archieve Digital Datatable Functionality', function () {
    it('performs global search, limit, and pagination', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'Searchable Document']);
        Document::factory()->count(10)->create(['title' => 'Other Doc']);

        // Search
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&search=Searchable');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toBe('Searchable Document');

        // Limit & Pagination
        // Page 1 with limit 5
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&limit=5&page=1');
        expect($response->json('data'))->toHaveCount(5);
        expect($response->json('total'))->toBe(11);

        // Page 3 with limit 5 (should have 1 item left)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&limit=5&page=3');
        expect($response->json('data'))->toHaveCount(1);
    });

    it('performs individual column search', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'Target Title ABC', 'description' => 'Target Desc']);
        Document::factory()->create(['title' => 'Other Title', 'description' => 'Other Desc']);

        // Search by title (current service only supports title and classification_id specifically)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&title=Target Title ABC');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toBe('Target Title ABC');

        // Search by category
        $cat = Category::factory()->create(['name' => 'Specific Category']);
        $doc = Document::factory()->create();
        $doc->categories()->attach($cat->id);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&category=Specific Category');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($doc->id);

        // Search by division
        $div = Division::factory()->create(['name' => 'Specific Division']);
        $docDiv = Document::factory()->create();
        $docDiv->divisions()->attach($div->id, ['allocated_size' => 100]);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&division=Specific Division');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docDiv->id);

        // Search by uploader
        $uploader = User::factory()->create(['name' => 'Specific Uploader']);
        $docUp = Document::factory()->create(['uploaded_by' => $uploader->id]);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&uploader=Specific Uploader');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docUp->id);

        // Search by file_size
        $docSize = Document::factory()->create(['file_size' => 123456]);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&file_size=123456');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docSize->id);

        // Search by created_at (month)
        $docDate = Document::factory()->create(['created_at' => '2025-05-15 10:00:00']);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&created_at=2025-05');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docDate->id);
    });

    it('sorts data correctly', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'AAA']);
        Document::factory()->create(['title' => 'ZZZ']);

        // Sort Asc
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&sort_by=title&sort_direction=asc');
        expect($response->json('data.0.title'))->toBe('AAA');

        // Sort Desc
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&sort_by=title&sort_direction=desc');
        expect($response->json('data.0.title'))->toBe('ZZZ');
    });
});

describe('Archieve Digital Export', function () {
    it('downloads xlsx file on print excel', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        Document::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=all');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $contentDisposition = $response->headers->get('Content-Disposition');
        expect($contentDisposition)->toContain('.xlsx');
    });
});

describe('Archieve Digital Management (CRUD)', function () {
    it('allows ManageDivision and ManageAll to access create and edit pages', function () {
        $manageDiv = createDigitalUser([ArchieveUserPermission::ManageDivision->value]);
        $manageAll = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $doc = Document::factory()->create();

        $this->actingAs($manageDiv)->get('/archieve/documents/create')->assertStatus(200);
        $this->actingAs($manageDiv)->get("/archieve/documents/{$doc->id}/edit")->assertStatus(200);
        
        $this->actingAs($manageAll)->get('/archieve/documents/create')->assertStatus(200);
        $this->actingAs($manageAll)->get("/archieve/documents/{$doc->id}/edit")->assertStatus(200);
    });

    it('denies management routes for unauthorized users', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        $doc = Document::factory()->create();

        $this->actingAs($user)->get('/archieve/documents/create')->assertStatus(403);
        $this->actingAs($user)->post('/archieve/documents', [])->assertStatus(403);
        $this->actingAs($user)->get("/archieve/documents/{$doc->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/archieve/documents/{$doc->id}", [])->assertStatus(403);
        $this->actingAs($user)->delete("/archieve/documents/{$doc->id}")->assertStatus(403);
    });

    it('stores file and splits file_size as allocated_size across multiple divisions', function () {
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $divs = Division::factory()->count(2)->create();
        
        // Initialize storage for divisions
        foreach($divs as $div) {
            DivisionStorage::factory()->create(['division_id' => $div->id, 'used_size' => 0, 'max_size' => 1000000]);
        }

        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100); // 100 KB
        $expectedSize = $file->getSize();
        $expectedAllocated = (int) floor($expectedSize / 2);

        $response = $this->actingAs($user)->post('/archieve/documents', [
            'title' => 'Multi-Div Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => $divs->pluck('id')->toArray(),
            'file' => $file
        ]);

        $response->assertRedirect('/archieve/documents');
        
        $doc = Document::where('title', 'Multi-Div Doc')->first();
        expect($doc->file_size)->toBe($expectedSize);
        Storage::disk('public')->assertExists($doc->file_path);

        foreach($divs as $div) {
            $pivot = $doc->divisions()->where('divisions.id', $div->id)->first()->pivot;
            expect($pivot->allocated_size)->toBe($expectedAllocated);
            
            $storage = DivisionStorage::where('division_id', $div->id)->first();
            expect($storage->used_size)->toBe($expectedAllocated);
        }
    });

    it('enforces division scope for ManageDivision users', function () {
        $divUser = Division::factory()->create();
        $divOther = Division::factory()->create();
        $user = createDigitalUser([ArchieveUserPermission::ManageDivision->value], $divUser->id);
        
        DivisionStorage::factory()->create(['division_id' => $divUser->id, 'used_size' => 0, 'max_size' => 1000000]);
        
        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 50);

        // Try to post with OTHER division_ids
        $response = $this->actingAs($user)->post('/archieve/documents', [
            'title' => 'Locked Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => [$divOther->id], // Hacker attempt
            'file' => $file
        ]);

        $response->assertRedirect('/archieve/documents');

        $doc = Document::where('title', 'Locked Doc')->first();
        // Should only be attached to $divUser, not $divOther
        expect($doc->divisions->pluck('id')->toArray())->toBe([$divUser->id]);
    });

    it('recalculates storage correctly when file is updated', function () {
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $div = Division::factory()->create();
        $storage = DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 1000000,
            'used_size' => 0
        ]);

        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        
        // Initial store manually to bypass controller for setup
        $file1Size = 100 * 1024;
        $doc = Document::factory()->create([
            'file_size' => $file1Size,
            'file_path' => 'old_path.pdf'
        ]);
        $doc->divisions()->attach($div->id, ['allocated_size' => $file1Size]);
        $storage->increment('used_size', $file1Size);

        // Update with new larger file
        $file2 = UploadedFile::fake()->create('new.pdf', 500); // 500 KB
        $size2 = $file2->getSize();

        // Use POST with _method=PUT because of multipart/form-data
        $this->actingAs($user)->post("/archieve/documents/{$doc->id}", [
            '_method' => 'PUT',
            'title' => 'Updated Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => [$div->id],
            'file' => $file2
        ]);

        $storage->refresh();
        expect($storage->used_size)->toBe($size2);
        
        $doc->refresh();
        expect($doc->file_size)->toBe($size2);
    });

    it('releases storage when document is deleted', function () {
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $div = Division::factory()->create();
        $storage = DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 1000000,
            'used_size' => 50000
        ]);

        $doc = Document::factory()->create(['file_size' => 50000, 'file_path' => 'temp.pdf']);
        $doc->divisions()->attach($div->id, ['allocated_size' => 50000]);

        $this->actingAs($user)->delete("/archieve/documents/{$doc->id}");

        $storage->refresh();
        expect($storage->used_size)->toBe(0);
        expect(Document::find($doc->id))->toBeNull();
    });

    it('validates storage limit before saving', function () {
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $div = Division::factory()->create(['name' => 'Full Div']);
        DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 2000, // Small
            'used_size' => 1500
        ]);

        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        $file = UploadedFile::fake()->create('big.pdf', 1); // 1 KB = 1024 bytes
        // 1500 + 1024 = 2524 > 2000

        $response = $this->actingAs($user)->post('/archieve/documents', [
            'title' => 'Too Big Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => [$div->id],
            'file' => $file
        ]);

        $response->assertSessionHasErrors(['file']);
        expect(Document::where('title', 'Too Big Doc')->count())->toBe(0);
    });
});

describe('Archieve Digital Reports', function () {
    it('allows access to division reports with ViewReportDivision', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewReportDivision->value]);
        
        $this->actingAs($user)->get('/archieve/reports')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/reports/all')->assertStatus(403);
    });

    it('allows access to global reports with ViewReportAll', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewReportAll->value]);
        
        $this->actingAs($user)->get('/archieve/reports/all')->assertStatus(200);
    });

    it('scopes division report data correctly', function () {
        $divA = Division::factory()->create(['name' => 'Div A']);
        $divB = Division::factory()->create(['name' => 'Div B']);
        $userA = createDigitalUser([ArchieveUserPermission::ViewReportDivision->value], $divA->id);

        // Doc for Div A (100 MB)
        $docA = Document::factory()->create(['file_size' => 100 * 1024 * 1024]);
        $docA->divisions()->attach($divA->id, ['allocated_size' => 100 * 1024 * 1024]);

        // Doc for Div B (50 MB)
        $docB = Document::factory()->create(['file_size' => 50 * 1024 * 1024]);
        $docB->divisions()->attach($divB->id, ['allocated_size' => 50 * 1024 * 1024]);

        $response = $this->actingAs($userA)->get('/archieve/reports');
        $data = $response->inertiaPage()['props']['reportData'];

        expect($data['overview_stats']['total_documents'])->toBe(1);
        expect($data['overview_stats']['total_size'])->toBe(100 * 1024 * 1024);
    });

    it('denies division report access if user has no division_id', function () {
        $user = createDigitalUser([ArchieveUserPermission::ViewReportDivision->value], null);
        // Ensure user really has no division
        $user->update(['division_id' => null]);

        $this->actingAs($user)->get('/archieve/reports')->assertStatus(403);
    });
});
