<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VisitorManagement\Models\VisitorFeedbackQuestion;
use Modules\VisitorManagement\Enums\VisitorUserPermission;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary permissions
    foreach (VisitorUserPermission::values() as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

// ============================================================================
// 1. Access Control
// ============================================================================

describe('Access Control', function () {
    test('user with ViewFeedbackQuestion permission can access index, datatable, and excel export', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewFeedbackQuestion->value);

        $this->actingAs($user)->get('/visitor/feedback-questions')->assertOk();
        $this->actingAs($user)->get('/visitor/feedback-questions/datatable')->assertOk();
        $this->actingAs($user)->get('/visitor/feedback-questions/print/excel')->assertOk();
    });

    test('user with ManageFeedbackQuestion permission can access CRUD operations', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value);
        $question = VisitorFeedbackQuestion::factory()->create();

        $this->actingAs($user)->get('/visitor/feedback-questions/create')->assertOk();
        $this->actingAs($user)->post('/visitor/feedback-questions/store', [
            'question' => 'Test Question?',
            'is_active' => true
        ])->assertRedirect(route('visitor.feedback-questions.index'));

        $this->actingAs($user)->get("/visitor/feedback-questions/{$question->id}/edit")->assertOk();
        $this->actingAs($user)->put("/visitor/feedback-questions/{$question->id}/update", [
            'question' => 'Updated Question?',
            'is_active' => false
        ])->assertRedirect(route('visitor.feedback-questions.index'));

        $this->actingAs($user)->delete("/visitor/feedback-questions/{$question->id}/delete")->assertRedirect();
    });

    test('user without permission cannot access feedback question routes', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/visitor/feedback-questions')->assertForbidden();
        $this->actingAs($user)->get('/visitor/feedback-questions/datatable')->assertForbidden();
        $this->actingAs($user)->get('/visitor/feedback-questions/create')->assertForbidden();
        $this->actingAs($user)->post('/visitor/feedback-questions/store')->assertForbidden();
        $this->actingAs($user)->get('/visitor/feedback-questions/print/excel')->assertForbidden();
    });
});

// ============================================================================
// 2. Form Validation
// ============================================================================

describe('Form Validation', function () {
    test('validation rules on create', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value);

        // Question is required
        $this->actingAs($user)->post('/visitor/feedback-questions/store', [
            'question' => '',
        ])->assertSessionHasErrors(['question']);

        // Question must be unique
        VisitorFeedbackQuestion::factory()->create(['question' => 'Existing Question?']);
        $this->actingAs($user)->post('/visitor/feedback-questions/store', [
            'question' => 'Existing Question?',
        ])->assertSessionHasErrors(['question']);
    });

    test('validation rules on update', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ManageFeedbackQuestion->value);
        $question = VisitorFeedbackQuestion::factory()->create(['question' => 'Original Question?']);
        $otherQuestion = VisitorFeedbackQuestion::factory()->create(['question' => 'Other Question?']);

        // Question must be unique except for current item
        $this->actingAs($user)->put("/visitor/feedback-questions/{$question->id}/update", [
            'question' => 'Other Question?',
        ])->assertSessionHasErrors(['question']);

        $this->actingAs($user)->put("/visitor/feedback-questions/{$question->id}/update", [
            'question' => 'Original Question?', // Same as current is fine
        ])->assertSessionHasNoErrors();
    });
});

// ============================================================================
// 3. Datatable Features
// ============================================================================

describe('Datatable Features', function () {
    test('handles search, limit, pagination and sort successfully', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewFeedbackQuestion->value);

        // Create multiple questions
        VisitorFeedbackQuestion::factory()->create(['question' => 'How was the service?']);
        VisitorFeedbackQuestion::factory()->create(['question' => 'Was the room clean?']);
        VisitorFeedbackQuestion::factory()->create(['question' => 'Are you satisfied?']);
        
        // Search
        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?search=service');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.question', 'How was the service?');

        // Limit
        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?limit=1');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('per_page', 1);

        // Sort
        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?sort_by=question&sort_direction=desc');
        $response->assertJsonPath('data.0.question', 'Was the room clean?');
        
        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?sort_by=question&sort_direction=asc');
        $response->assertJsonPath('data.0.question', 'Are you satisfied?');
    });

    test('handles footer column search correctly', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewFeedbackQuestion->value);

        VisitorFeedbackQuestion::factory()->create(['question' => 'Specific Question']);
        VisitorFeedbackQuestion::factory()->create(['question' => 'Other Question']);

        // Search by question column
        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?question=Specific');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.question', 'Specific Question');

        // Search by status column (active)
        VisitorFeedbackQuestion::factory()->create(['question' => 'Active Question', 'is_active' => true]);
        VisitorFeedbackQuestion::factory()->create(['question' => 'Inactive Question', 'is_active' => false]);
        
        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?status=active');
        $response->assertJsonFragment(['question' => 'Active Question']);
        $response->assertJsonMissing(['question' => 'Inactive Question']);

        $response = $this->actingAs($user)->get('/visitor/feedback-questions/datatable?status=inactive');
        $response->assertJsonFragment(['question' => 'Inactive Question']);
        $response->assertJsonMissing(['question' => 'Active Question']);
    });
});

// ============================================================================
// 4. Excel Export
// ============================================================================

describe('Excel Export', function () {
    test('generates xlsx file', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(VisitorUserPermission::ViewFeedbackQuestion->value);

        VisitorFeedbackQuestion::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/visitor/feedback-questions/print/excel');
        $response->assertOk();
        
        $contentDisposition = $response->headers->get('content-disposition');
        expect($contentDisposition)->toContain('.xlsx');
    });
});
