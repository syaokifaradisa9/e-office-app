<?php

use App\Models\User;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VisitorManagement\Models\Visitor;
use Modules\VisitorManagement\Models\VisitorPurpose;
use Modules\VisitorManagement\Models\VisitorFeedback;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Modules\VisitorManagement\Enums\VisitorStatus;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary permissions
    foreach (VisitorUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

/**
 * 1. Jika user memiliki permission Lihat Data Pengunjung maka dapat mengunjungi 
 * /visitor /visitor/datatable /visitor/print/excel /visitor/{id}
 */
test('user with View Data permission can access visitor management routes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::ViewData->value);
    
    $visitor = Visitor::factory()->create();

    $this->actingAs($user)->get('/visitor')->assertOk();
    $this->actingAs($user)->get('/visitor/datatable')->assertOk();
    $this->actingAs($user)->get('/visitor/export')->assertOk(); 
    $this->actingAs($user)->get("/visitor/{$visitor->id}")->assertOk();
});

/**
 * 2. Jika user memiliki permission Konfirmasi Kunjungan maka bisa mengakses /visitor/{id}/confirm 
 * dan pastikan status datanya menjadi Disetujui atau Ditolak dan verifikasi catatan konfirmasi 
 * juga masuk ke dalam database
 */
test('user with Confirm Visit permission can approve or reject a visit', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::ConfirmVisit->value);
    
    $visitor = Visitor::factory()->create(['status' => VisitorStatus::Pending]);

    // Approve
    $this->actingAs($user)->post("/visitor/{$visitor->id}/confirm", [
        'status' => 'approved',
        'admin_note' => 'Welcome to our office'
    ])->assertRedirect();

    $visitor->refresh();
    expect($visitor->status)->toBe(VisitorStatus::Approved);
    expect($visitor->admin_note)->toBe('Welcome to our office');
    expect($visitor->confirmed_by)->toBe($user->id);

    // Reject another one
    $visitor2 = Visitor::factory()->create(['status' => VisitorStatus::Pending]);
    $this->actingAs($user)->post("/visitor/{$visitor2->id}/confirm", [
        'status' => 'rejected',
        'admin_note' => 'Sorry, we are busy'
    ])->assertRedirect();

    $visitor2->refresh();
    expect($visitor2->status)->toBe(VisitorStatus::Rejected);
    expect($visitor2->admin_note)->toBe('Sorry, we are busy');
});

/**
 * 3. Jika user memiliki permisison Buat Undangan Tamu maka user bisa mengakses /visitor/create 
 * /visitor/store dan pastikan status datanya Diundang
 */
test('user with Create Invitation permission can create an invitation', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::CreateInvitation->value);
    
    $division = Division::factory()->create();
    $purpose = VisitorPurpose::factory()->create();

    $this->actingAs($user)->get('/visitor/create')->assertOk();
    
    $this->actingAs($user)->post('/visitor/store-invitation', [
        'visitor_name' => 'Tamu Undangan',
        'phone_number' => '08123456789',
        'organization' => 'PT Testing',
        'division_id' => $division->id,
        'purpose_id' => $purpose->id,
        'purpose_detail' => 'Meeting Penting',
        'visitor_count' => 5
    ])->assertRedirect(route('visitor.index'));

    $visitor = Visitor::where('visitor_name', 'Tamu Undangan')->first();
    expect($visitor)->not->toBeNull();
    expect($visitor->status)->toBe(VisitorStatus::Invited);
});

/**
 * 4. Jika user memiliki permission Lihat Kritik Saran Pengunjung maka dapat mengakses 
 * /visitor/criticism-suggestions /visitor/criticism-suggestions/datatable /visitor/criticism-suggestions/print/excel
 */
test('user with View Criticism permission can access criticism suggestions routes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::ViewCriticismFeedback->value);

    $this->actingAs($user)->get('/visitor/criticism-suggestions')->assertOk();
    $this->actingAs($user)->get('/visitor/criticism-suggestions/datatable')->assertOk();
    $this->actingAs($user)->get('/visitor/criticism-suggestions/export')->assertOk();
});

/**
 * 5. Jika user memiliki permisison Kelola Kritik Saran Pengunjung maka dapat mengakses 
 * /visitor/criticism-suggestions/{id}/mark-as-read
 */
test('user with Manage Criticism permission can mark feedback as read', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::ManageCriticismFeedback->value);
    
    $visitor = Visitor::factory()->create();
    $feedback = VisitorFeedback::create([
        'visitor_id' => $visitor->id,
        'feedback_note' => 'Bagus sekali',
        'is_read' => false
    ]);

    $this->actingAs($user)->post("/visitor/criticism-suggestions/{$feedback->id}/mark-as-read")
        ->assertRedirect();

    $feedback->refresh();
    expect($feedback->is_read)->toBeTrue();
});

/**
 * 6. jika user memiliki permisison Lihat Laporan Penunjung maka dapat mengakses /visitor/reports?
 */
test('user with View Report permission can access visitor reports', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::ViewReport->value);

    $this->actingAs($user)->get('/visitor/reports')->assertOk();
});

/**
 * 7. Jika user memiliki permisison Lihat Dashboard Penunjung maka akan muncul data summary 
 * pengunjung di dashboard
 */
test('user with View Dashboard permission can see statistics in dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(VisitorUserPermission::ViewDashboard->value);

    $response = $this->actingAs($user)->get('/visitor/dashboard');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('VisitorManagement/Visitor/Dashboard')
        ->has('stats')
        ->has('purposeDistribution')
        ->has('weeklyTrend')
    );
});

/**
 * 8. Pastikan form search, sort, limit dan pagination tiap halaman berfungsi dengan baik
 * 9. pastikan form search footer tiap kolom tiap halaman berfungsi dengan baik
 */
describe('Visitor List Datatable Features', function () {
    test('visitor datatable handles search, sort, limit, and pagination', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewData->value);

        Visitor::factory()->create(['visitor_name' => 'Alpha', 'organization' => 'Org A', 'created_at' => now()->subMinutes(10)]);
        Visitor::factory()->create(['visitor_name' => 'Beta', 'organization' => 'Org B', 'created_at' => now()->subMinutes(5)]);
        Visitor::factory()->create(['visitor_name' => 'Gamma', 'organization' => 'Org C', 'created_at' => now()]);

        // Global Search
        $response = $this->actingAs($user)->get('/visitor/datatable?search=Alpha');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.visitor_name', 'Alpha');

        // Sort by visitor_name desc
        $response = $this->actingAs($user)->get('/visitor/datatable?sort_by=visitor_name&sort_direction=desc');
        $response->assertJsonPath('data.0.visitor_name', 'Gamma');

        // Limit
        $response = $this->actingAs($user)->get('/visitor/datatable?limit=1');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('per_page', 1);

        // Pagination
        $response = $this->actingAs($user)->get('/visitor/datatable?limit=1&page=2');
        $response->assertJsonPath('current_page', 2);
    });

    test('visitor datatable handles footer column search', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewData->value);

        Visitor::factory()->create(['visitor_name' => 'John Doe', 'organization' => 'Google']);
        Visitor::factory()->create(['visitor_name' => 'Jane Smith', 'organization' => 'Microsoft']);

        // Footer search by visitor_name
        $response = $this->actingAs($user)->get('/visitor/datatable?visitor_name=John');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.visitor_name', 'John Doe');

        // Footer search by organization
        $response = $this->actingAs($user)->get('/visitor/datatable?organization=Microsoft');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.visitor_name', 'Jane Smith');
    });
});

describe('Criticism Suggestions Datatable Features', function () {
    test('criticism suggestions datatable handles search and limit', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewCriticismFeedback->value);

        $v1 = Visitor::factory()->create(['visitor_name' => 'Alpha', 'organization' => 'Org A']);
        $v2 = Visitor::factory()->create(['visitor_name' => 'Beta', 'organization' => 'Org B']);

        VisitorFeedback::create(['visitor_id' => $v1->id, 'feedback_note' => 'Note Alpha', 'is_read' => false]);
        VisitorFeedback::create(['visitor_id' => $v2->id, 'feedback_note' => 'Note Beta', 'is_read' => false]);

        // Search
        $response = $this->actingAs($user)->get('/visitor/criticism-suggestions/datatable?search=Alpha');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.visitor_name', 'Alpha');

        // Limit
        $response = $this->actingAs($user)->get('/visitor/criticism-suggestions/datatable?limit=1');
        $response->assertJsonCount(1, 'data');

        // Sort by visitor_name desc
        $response = $this->actingAs($user)->get('/visitor/criticism-suggestions/datatable?sort_by=visitor_name&sort_direction=desc');
        $response->assertJsonPath('data.0.visitor_name', 'Beta');

        // Footer search: visitor_name
        $response = $this->actingAs($user)->get('/visitor/criticism-suggestions/datatable?visitor_name=Alpha');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.visitor_name', 'Alpha');

        // Footer search: status (unread)
        $response = $this->actingAs($user)->get('/visitor/criticism-suggestions/datatable?status=read');
        $response->assertJsonCount(0, 'data');
    });
});

/**
 * 10. pastikan print/excel tiap halaman menghasilkan file xlsx
 */
describe('Excel Export Verification', function () {
    test('visitor export produces xlsx file', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewData->value);
        Visitor::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/visitor/export');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        expect($response->headers->get('content-disposition'))->toContain('.xlsx');
    });

    test('criticism suggestions export produces xlsx file', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewCriticismFeedback->value);
        
        $v = Visitor::factory()->create();
        VisitorFeedback::create(['visitor_id' => $v->id, 'feedback_note' => 'Good', 'is_read' => false]);

        $response = $this->actingAs($user)->get('/visitor/criticism-suggestions/export');
        $response->assertOk();
        expect($response->headers->get('content-disposition'))->toContain('.xlsx');
    });
});
