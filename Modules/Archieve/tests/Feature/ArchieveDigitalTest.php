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

describe('Digital Search Integration', function () {
    /**
     * Test pencarian dokumen dengan izin SearchDocument dan SearchAllScope.
     * Memastikan user dapat mengakses halaman pencarian dan mendapatkan hasil yang sesuai.
     */
    it('accesses search page and performs valid search', function () {
        // 1. Persiapan user dengan izin pencarian global
        $user = createDigitalUser([ArchieveUserPermission::SearchAllScope->value]);
        
        // 2. Persiapan data dokumen untuk dicari
        $doc = Document::factory()->create(['title' => 'FindMe']);
        
        // 3. Akses halaman index pencarian
        $this->actingAs($user)->get('/archieve/documents/search')->assertStatus(200);
        
        // 4. Melakukan request pencarian ke API hasil
        $response = $this->actingAs($user)->get('/archieve/documents/search/results?search=FindMe');
        
        // 5. Validasi status dan jumlah data yang ditemukan
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
    });

    /**
     * Test akses halaman pencarian untuk user dengan izin SearchDivisionScope saja.
     */
    it('allows access to search for users with only SearchDivisionScope', function () {
        // 1. Persiapan user dengan izin divisi
        $user = createDigitalUser([ArchieveUserPermission::SearchDivisionScope->value]);
        
        // 2. Akses halaman pencarian (seharusnya dizinkan)
        $this->actingAs($user)->get('/archieve/documents/search')->assertStatus(200);
    });

    /**
     * Test akses halaman pencarian untuk user dengan izin SearchPersonalScope saja.
     */
    it('allows access to search for users with only SearchPersonalScope', function () {
        // 1. Persiapan user dengan izin personal
        $user = createDigitalUser([ArchieveUserPermission::SearchPersonalScope->value]);
        
        // 2. Akses halaman pencarian (seharusnya diizinkan)
        $this->actingAs($user)->get('/archieve/documents/search')->assertStatus(200);
    });

    /**
     * Test pembatasan hasil pencarian berdasarkan divisi (SearchDivisionScope).
     * Memastikan user hanya dapat melihat dokumen yang terkait dengan divisinya.
     */
    it('scopes search to specific division for SearchDivisionScope', function () {
        // 1. Persiapan data divisi
        $div1 = Division::factory()->create(['name' => 'Divisi 1']);
        $div2 = Division::factory()->create(['name' => 'Divisi 2']);
        
        // 2. Persiapan user di Divisi 1
        $user = createDigitalUser([ArchieveUserPermission::SearchDivisionScope->value], $div1->id);

        // 3. Persiapan dokumen untuk Divisi 1
        $doc1 = Document::factory()->create(['title' => 'Doc Divisi 1']);
        $doc1->divisions()->attach($div1->id, ['allocated_size' => 0]);

        // 4. Persiapan dokumen untuk Divisi 2
        $doc2 = Document::factory()->create(['title' => 'Doc Divisi 2']);
        $doc2->divisions()->attach($div2->id, ['allocated_size' => 0]);

        // 5. Eksekusi pencarian
        $response = $this->actingAs($user)->get('/archieve/documents/search/results');
        $response->assertStatus(200);
        
        // 6. Validasi bahwa hanya dokumen Divisi 1 yang muncul
        $titles = collect($response->json('data'))->pluck('title');
        expect($titles)->toContain('Doc Divisi 1');
        expect($titles)->not->toContain('Doc Divisi 2');
    });

    /**
     * Test hasil pencarian global (SearchAllScope).
     * Memastikan user dapat melihat semua dokumen tanpa batasan divisi.
     */
    it('shows all documents for SearchAllScope regardless of division', function () {
        // 1. Persiapan user dengan akses keseluruhan
        $div1 = Division::factory()->create();
        $user = createDigitalUser([ArchieveUserPermission::SearchAllScope->value]);

        // 2. Tambah dokumen tanpa divisi dan dokumen dengan divisi lain
        Document::factory()->create(['title' => 'Doc A']);
        $docB = Document::factory()->create(['title' => 'Doc B']);
        $docB->divisions()->attach($div1->id, ['allocated_size' => 0]);

        // 3. Validasi hasil pencarian menampilkan semua dokumen (2 item)
        $response = $this->actingAs($user)->get('/archieve/documents/search/results');
        expect($response->json('data'))->toHaveCount(2);
    });

    /**
     * Test pembatasan hasil pencarian personal (SearchPersonalScope).
     * Memastikan user hanya melihat dokumen yang secara eksplisit dibagikan kepadanya (melalui pivot table users).
     */
    /**
     * Test pembatasan hasil pencarian personal (SearchPersonalScope).
     * Memastikan user hanya melihat dokumen yang secara eksplisit dibagikan kepadanya (melalui pivot table users).
     */
    it('filters search to personal documents for SearchPersonalScope', function () {
        // 1. Persiapan User A (penguji) dan User B
        $userA = createDigitalUser([ArchieveUserPermission::SearchPersonalScope->value]);
        $userB = User::factory()->create();

        // 2. Buat dokumen yang dibagikan ke User A
        $myDoc = Document::factory()->create(['title' => 'Shared with Me']);
        $myDoc->users()->attach($userA->id);

        // 3. Buat dokumen yang tidak dibagikan ke User A
        Document::factory()->create(['title' => 'Not Shared with Me']);

        // 4. Eksekusi pencarian
        $response = $this->actingAs($userA)->get('/archieve/documents/search/results');
        $titles = collect($response->json('data'))->pluck('title');
        
        // 5. Validasi hanya dokumen milik user yang muncul
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
    /**
     * Memastikan user dengan izin ViewPersonal dapat mengakses index, datatable, dan print excel versi personal.
     */
    it('allows users with ViewPersonal to access document routes', function () {
        // 1. Persiapan user dengan izin personal
        $user = createDigitalUser([ArchieveUserPermission::ViewPersonal->value]);
        
        // 2. Validasi akses ke berbagai endpoint dokumen
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=personal')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=personal')->assertStatus(200);
    });

    /**
     * Memastikan user dengan izin ViewDivision dapat mengakses index, datatable, dan print excel versi divisi.
     */
    it('allows users with ViewDivision to access document routes', function () {
        // 1. Persiapan user dengan izin divisi
        $user = createDigitalUser([ArchieveUserPermission::ViewDivision->value]);
        
        // 2. Validasi akses ke berbagai endpoint dokumen
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=division')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=division')->assertStatus(200);
    });

    /**
     * Memastikan user dengan izin ViewAll dapat mengakses index, datatable, dan print excel versi keseluruhan.
     */
    it('allows users with ViewAll to access document routes', function () {
        // 1. Persiapan user dengan izin keseluruhan
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        // 2. Validasi akses ke berbagai endpoint dokumen
        $this->actingAs($user)->get('/archieve/documents')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=all')->assertStatus(200);
    });
});

describe('Archieve Digital Data Scoping', function () {
    /**
     * Memastikan data di datatable hanya menampilkan dokumen personal milik user yang bersangkutan.
     */
    it('scopes datatable to personal user', function () {
        // 1. Persiapan 2 user berbeda
        $userA = createDigitalUser([ArchieveUserPermission::ViewPersonal->value]);
        $userB = createDigitalUser([ArchieveUserPermission::ViewPersonal->value]);

        // 2. Buat dokumen untuk User A
        $docA = Document::factory()->create(['title' => 'Personal Doc A']);
        $docA->users()->attach($userA->id);

        // 3. Buat dokumen untuk User B
        $docB = Document::factory()->create(['title' => 'Personal Doc B']);
        $docB->users()->attach($userB->id);

        // 4. Request datatable sebagai User A
        $response = $this->actingAs($userA)->get('/archieve/documents/datatable?view_type=personal');
        $data = $response->json('data');

        // 5. Validasi hanya dokumen milik User A yang muncul
        expect($data)->toHaveCount(1);
        expect($data[0]['title'])->toBe('Personal Doc A');
    });

    /**
     * Memastikan data di datatable hanya menampilkan dokumen milik divisi user.
     */
    it('scopes datatable to division', function () {
        // 1. Persiapan 2 divisi berbeda
        $divA = Division::factory()->create();
        $divB = Division::factory()->create();
        
        // 2. Persiapan user di Divisi A
        $userA = createDigitalUser([ArchieveUserPermission::ViewDivision->value], $divA->id);
        
        // 3. Buat dokumen untuk Divisi A
        $docA = Document::factory()->create(['title' => 'Division Doc A']);
        $docA->divisions()->attach($divA->id, ['allocated_size' => 100]);

        // 4. Buat dokumen untuk Divisi B
        $docB = Document::factory()->create(['title' => 'Division Doc B']);
        $docB->divisions()->attach($divB->id, ['allocated_size' => 100]);

        // 5. Request datatable versi divisi
        $response = $this->actingAs($userA)->get('/archieve/documents/datatable?view_type=division');
        $data = $response->json('data');

        // 6. Validasi hanya dokumen Divisi A yang muncul
        expect($data)->toHaveCount(1);
        expect($data[0]['title'])->toBe('Division Doc A');
    });

    /**
     * Memastikan datatable menampilkan semua data jika memiliki izin ViewAll.
     */
    /**
     * Memastikan datatable menampilkan semua data jika memiliki izin ViewAll.
     */
    it('returns all documents for ViewAll permission', function () {
        // 1. Persiapan user dengan izin keseluruhan
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        // 2. Buat beberapa dokumen sembarang
        Document::factory()->count(3)->create();

        // 3. Request datatable versi keseluruhan
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all');
        $data = $response->json('data');

        // 4. Validasi semua dokumen (3) muncul
        expect($data)->toHaveCount(3);
    });
});

describe('Archieve Digital Datatable Functionality', function () {
    /**
     * Test fitur pencarian global, pembatasan jumlah data (limit), dan halaman (pagination) di datatable.
     */
    it('performs global search, limit, and pagination', function () {
        // 1. Persiapan user dan data
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'Searchable Document']);
        Document::factory()->count(10)->create(['title' => 'Other Doc']);

        // 2. Test Pencarian Global (Search)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&search=Searchable');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toBe('Searchable Document');

        // 3. Test Limit & Pagination
        // Halaman 1 dengan limit 5 (seharusnya dapat 5 dari 11 total)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&limit=5&page=1');
        expect($response->json('data'))->toHaveCount(5);
        expect($response->json('total'))->toBe(11);

        // Halaman 3 dengan limit 5 (seharusnya sisa 1 item)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&limit=5&page=3');
        expect($response->json('data'))->toHaveCount(1);
    });

    /**
     * Test fitur pencarian spesifik per kolom (Title, Category, Division, Uploader, Size, Date).
     */
    it('performs individual column search', function () {
        // 1. Persiapan user
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'Target Title ABC', 'description' => 'Target Desc']);
        Document::factory()->create(['title' => 'Other Title', 'description' => 'Other Desc']);

        // 2. Search berdasarkan Judul
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&title=Target Title ABC');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toBe('Target Title ABC');

        // 3. Search berdasarkan Kategori
        $cat = Category::factory()->create(['name' => 'Specific Category']);
        $doc = Document::factory()->create();
        $doc->categories()->attach($cat->id);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&category=Specific Category');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($doc->id);

        // 4. Search berdasarkan Divisi
        $div = Division::factory()->create(['name' => 'Specific Division']);
        $docDiv = Document::factory()->create();
        $docDiv->divisions()->attach($div->id, ['allocated_size' => 100]);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&division=Specific Division');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docDiv->id);

        // 5. Search berdasarkan Pengunggah
        $uploader = User::factory()->create(['name' => 'Specific Uploader']);
        $docUp = Document::factory()->create(['uploaded_by' => $uploader->id]);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&uploader=Specific Uploader');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docUp->id);

        // 6. Search berdasarkan Ukuran File
        $docSize = Document::factory()->create(['file_size' => 123456]);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&file_size=123456');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docSize->id);

        // 7. Search berdasarkan Tanggal (Bulan/Tahun)
        $docDate = Document::factory()->create(['created_at' => '2025-05-15 10:00:00']);
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&created_at=2025-05');
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($docDate->id);
    });

    /**
     * Test fitur pengurutan data (Sorting) di datatable.
     */
    /**
     * Test fitur pengurutan data (Sorting) di datatable.
     */
    it('performs data sorting correctly', function () {
        // 1. Persiapan user dan data
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        
        Document::factory()->create(['title' => 'AAA']);
        Document::factory()->create(['title' => 'ZZZ']);

        // 2. Sort Ascending (AAA seharusnya pertama)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&sort_by=title&sort_direction=asc');
        expect($response->json('data.0.title'))->toBe('AAA');

        // 3. Sort Descending (ZZZ seharusnya pertama)
        $response = $this->actingAs($user)->get('/archieve/documents/datatable?view_type=all&sort_by=title&sort_direction=desc');
        expect($response->json('data.0.title'))->toBe('ZZZ');
    });
});

describe('Archieve Digital Export', function () {
    /**
     * Test fitur export data ke Excel (.xlsx).
     */
    it('downloads xlsx file on print excel', function () {
        // 1. Persiapan user dan data
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        Document::factory()->count(2)->create();

        // 2. Request print excel
        $response = $this->actingAs($user)->get('/archieve/documents/print/excel?view_type=all');
        
        // 3. Validasi status dan header file
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $contentDisposition = $response->headers->get('Content-Disposition');
        expect($contentDisposition)->toContain('.xlsx');
    });
});

describe('Archieve Digital Management (CRUD)', function () {
    /**
     * Memastikan user dengan izin ManageDivision atau ManageAll dapat mengakses halaman tambah dan edit dokumen.
     */
    it('allows ManageDivision and ManageAll to access create and edit pages', function () {
        // 1. Persiapan user dengan izin manajemen
        $manageDiv = createDigitalUser([ArchieveUserPermission::ManageDivision->value]);
        $manageAll = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $doc = Document::factory()->create();

        // 2. Cek akses user dengan izin divisi
        $this->actingAs($manageDiv)->get('/archieve/documents/create')->assertStatus(200);
        $this->actingAs($manageDiv)->get("/archieve/documents/{$doc->id}/edit")->assertStatus(200);
        
        // 3. Cek akses user dengan izin keseluruhan
        $this->actingAs($manageAll)->get('/archieve/documents/create')->assertStatus(200);
        $this->actingAs($manageAll)->get("/archieve/documents/{$doc->id}/edit")->assertStatus(200);
    });

    /**
     * Memastikan user yang hanya punya izin View tidak bisa melakukan aksi manajemen (Create, Store, Edit, Update, Delete).
     */
    it('denies management routes for unauthorized users', function () {
        // 1. Persiapan user tanpa izin manajemen
        $user = createDigitalUser([ArchieveUserPermission::ViewAll->value]);
        $doc = Document::factory()->create();

        // 2. Validasi penolakan (403 Forbidden) di semua route manajemen
        $this->actingAs($user)->get('/archieve/documents/create')->assertStatus(403);
        $this->actingAs($user)->post('/archieve/documents', [])->assertStatus(403);
        $this->actingAs($user)->get("/archieve/documents/{$doc->id}/edit")->assertStatus(403);
        $this->actingAs($user)->put("/archieve/documents/{$doc->id}", [])->assertStatus(403);
        $this->actingAs($user)->delete("/archieve/documents/{$doc->id}")->assertStatus(403);
    });

    /**
     * Test proses simpan dokumen yang dibagikan ke beberapa divisi.
     * Memastikan file tersimpan di storage dan ukuran file terbagi (allocated_size) ke divisi terkait.
     */
    it('stores file and splits file_size as allocated_size across multiple divisions', function () {
        // 1. Persiapan user dan divisi (2 divisi)
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $divs = Division::factory()->count(2)->create();
        
        // 2. Inisialisasi kuota penyimpanan untuk tiap divisi
        foreach($divs as $div) {
            DivisionStorage::factory()->create(['division_id' => $div->id, 'used_size' => 0, 'max_size' => 1000000]);
        }

        // 3. Persiapan metadata dan file tiruan
        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100); // 100 KB
        $expectedSize = $file->getSize();
        $expectedAllocated = (int) floor($expectedSize / 2);

        // 4. Melakukan POST request untuk simpan dokumen
        $response = $this->actingAs($user)->post('/archieve/documents', [
            'title' => 'Multi-Div Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => $divs->pluck('id')->toArray(),
            'file' => $file
        ]);

        // 5. Validasi redirect dan keberadaan file di storage
        $response->assertRedirect('/archieve/documents');
        
        $doc = Document::where('title', 'Multi-Div Doc')->first();
        expect($doc->file_size)->toBe($expectedSize);
        Storage::disk('public')->assertExists($doc->file_path);

        // 6. Validasi alokasi ukuran data di pivot divisi dan model DivisionStorage
        foreach($divs as $div) {
            $pivot = $doc->divisions()->where('divisions.id', $div->id)->first()->pivot;
            expect($pivot->allocated_size)->toBe($expectedAllocated);
            
            $storage = DivisionStorage::where('division_id', $div->id)->first();
            expect($storage->used_size)->toBe($expectedAllocated);
        }
    });

    /**
     * Memastikan ManageDivision hanya bisa mengunggah ke divisinya sendiri, biarpun mencoba mengirim division_id lain.
     */
    it('enforces division scope for ManageDivision users', function () {
        // 1. Persiapan user di Divisi User
        $divUser = Division::factory()->create();
        $divOther = Division::factory()->create();
        $user = createDigitalUser([ArchieveUserPermission::ManageDivision->value], $divUser->id);
        
        DivisionStorage::factory()->create(['division_id' => $divUser->id, 'used_size' => 0, 'max_size' => 1000000]);
        
        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 50);

        // 2. Kirim POST request dengan mencoba menyisipkan division_id lain (Divisi Other)
        $response = $this->actingAs($user)->post('/archieve/documents', [
            'title' => 'Locked Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => [$divOther->id], // Upaya peretasan / bypass
            'file' => $file
        ]);

        $response->assertRedirect('/archieve/documents');

        // 3. Validasi dokumen tetap masuk ke divisi user, bukan divisi lain
        $doc = Document::where('title', 'Locked Doc')->first();
        expect($doc->divisions->pluck('id')->toArray())->toBe([$divUser->id]);
    });

    /**
     * Test fitur update dokumen dengan file baru yang lebih besar.
     * Memastikan quota penyimpanan terhitung ulang dengan benar (ukuran lama dikurangi, ukuran baru ditambah).
     */
    it('recalculates storage correctly when file is updated', function () {
        // 1. Persiapan user dan awal data
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $div = Division::factory()->create();
        $storage = DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 1000000,
            'used_size' => 0
        ]);

        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        
        // 2. Simulasi simpan dokumen awal (100 KB)
        $file1Size = 100 * 1024;
        $doc = Document::factory()->create([
            'file_size' => $file1Size,
            'file_path' => 'old_path.pdf'
        ]);
        $doc->divisions()->attach($div->id, ['allocated_size' => $file1Size]);
        $storage->increment('used_size', $file1Size);

        // 3. Melakukan Update dengan file baru yang lebih besar (500 KB)
        $file2 = UploadedFile::fake()->create('new.pdf', 500); // 500 KB
        $size2 = $file2->getSize();

        // Menggunakan POST dengan _method=PUT karena multipart/form-data
        $this->actingAs($user)->post("/archieve/documents/{$doc->id}", [
            '_method' => 'PUT',
            'title' => 'Updated Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => [$div->id],
            'file' => $file2
        ]);

        // 4. Validasi kuota penyimpanan terupdate menjadi ukuran file baru saja (bukan kumulatif)
        $storage->refresh();
        expect($storage->used_size)->toBe($size2);
        
        $doc->refresh();
        expect($doc->file_size)->toBe($size2);
    });

    /**
     * Test fitur hapus dokumen. Memastikan kuota penyimpanan dikosongkan kembali setelah dokumen dihapus.
     */
    it('releases storage when document is deleted', function () {
        // 1. Persiapan data awal yang menggunakan storage
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $div = Division::factory()->create();
        $storage = DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 1000000,
            'used_size' => 50000
        ]);

        $doc = Document::factory()->create(['file_size' => 50000, 'file_path' => 'temp.pdf']);
        $doc->divisions()->attach($div->id, ['allocated_size' => 50000]);

        // 2. Melakukan Delete request
        $this->actingAs($user)->delete("/archieve/documents/{$doc->id}");

        // 3. Validasi storage kembali ke 0 dan record dokumen hilang
        $storage->refresh();
        expect($storage->used_size)->toBe(0);
        expect(Document::find($doc->id))->toBeNull();
    });

    /**
     * Test validasi batas maksimum kuota penyimpanan sebelum simpan dokumen.
     */
    it('validates storage limit before saving', function () {
        // 1. Persiapan divisi dengan sisa kuota yang sangat tipis
        $user = createDigitalUser([ArchieveUserPermission::ManageAll->value]);
        $div = Division::factory()->create(['name' => 'Full Div']);
        DivisionStorage::factory()->create([
            'division_id' => $div->id, 
            'max_size' => 2000, // Kapasitas 2 KB
            'used_size' => 1500  // Terpakai 1.5 KB
        ]);

        // 2. Coba upload file sebesar 1 KB (sisa kuota hanya 0.5 KB)
        $class = DocumentClassification::factory()->create();
        $cat = Category::factory()->create();
        $file = UploadedFile::fake()->create('big.pdf', 1); // 1 KB
        
        $response = $this->actingAs($user)->post('/archieve/documents', [
            'title' => 'Too Big Doc',
            'classification_id' => $class->id,
            'category_ids' => [$cat->id],
            'division_ids' => [$div->id],
            'file' => $file
        ]);

        // 3. Validasi muncul error validasi storage
        $response->assertSessionHasErrors(['file']);
        expect(Document::where('title', 'Too Big Doc')->count())->toBe(0);
    });
});

describe('Archieve Digital Reports', function () {
    /**
     * Memastikan akses laporan divisi terbuka bagi user dengan izin ViewReportDivision.
     */
    it('allows access to division reports with ViewReportDivision', function () {
        // 1. Persiapan user
        $user = createDigitalUser([ArchieveUserPermission::ViewReportDivision->value]);
        
        // 2. Cek akses (laporan divisi OK, laporan keseluruhan NO)
        $this->actingAs($user)->get('/archieve/reports')->assertStatus(200);
        $this->actingAs($user)->get('/archieve/reports/all')->assertStatus(403);
    });

    /**
     * Memastikan akses laporan keseluruhan terbuka bagi user dengan izin ViewReportAll.
     */
    it('allows access to global reports with ViewReportAll', function () {
        // 1. Persiapan user
        $user = createDigitalUser([ArchieveUserPermission::ViewReportAll->value]);
        
        // 2. Cek akses laporan keseluruhan
        $this->actingAs($user)->get('/archieve/reports/all')->assertStatus(200);
    });

    /**
     * Memastikan data laporan divisi terfilter (scoping) hanya untuk divisi user yang login.
     */
    it('scopes division report data correctly', function () {
        // 1. Persiapan 2 divisi dan dokumen di masing-masing divisi
        $divA = Division::factory()->create(['name' => 'Div A']);
        $divB = Division::factory()->create(['name' => 'Div B']);
        $userA = createDigitalUser([ArchieveUserPermission::ViewReportDivision->value], $divA->id);

        // Dokumen untuk Divisi A (100 MB)
        $docA = Document::factory()->create(['file_size' => 100 * 1024 * 1024]);
        $docA->divisions()->attach($divA->id, ['allocated_size' => 100 * 1024 * 1024]);

        // Dokumen untuk Divisi B (50 MB)
        $docB = Document::factory()->create(['file_size' => 50 * 1024 * 1024]);
        $docB->divisions()->attach($divB->id, ['allocated_size' => 50 * 1024 * 1024]);

        // 2. Request laporan divisi sebagai User Divisi A
        $response = $this->actingAs($userA)->get('/archieve/reports');
        $data = $response->inertiaPage()['props']['reportData'];

        // 3. Validasi data yang muncul hanya milik Divisi A saja
        expect($data['overview_stats']['total_documents'])->toBe(1);
        expect($data['overview_stats']['total_size'])->toBe(100 * 1024 * 1024);
    });

    /**
     * Memastikan akses laporan divisi ditolak jika user tidak memiliki divisi yang terdaftar.
     */
    it('denies division report access if user has no division_id', function () {
        // 1. Persiapan user tanpa divisi
        $user = createDigitalUser([ArchieveUserPermission::ViewReportDivision->value], null);
        $user->update(['division_id' => null]);

        // 2. Validasi penolakan (403 Forbidden)
        $this->actingAs($user)->get('/archieve/reports')->assertStatus(403);
    });
});
