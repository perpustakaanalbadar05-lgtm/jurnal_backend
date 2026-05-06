<?php

namespace Tests\Feature;

use App\Models\Paper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaperWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_paper_workflow()
    {
        // 1. Setup Users
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $author = User::factory()->create(['role' => 'author', 'is_active' => true]);
        $reviewer = User::factory()->create(['role' => 'reviewer', 'is_active' => true]);

        // 2. Author submits a paper
        $response = $this->actingAs($author)->postJson('/api/papers', [
            'title' => 'Test Paper Title',
            'abstract' => 'This is a test abstract for the paper.',
            'keywords' => 'test, paper, workflow'
        ]);

        $response->assertStatus(201);
        $paperId = $response->json('id');
        $this->assertEquals('pending', $response->json('status'));

        // 3. Admin assigns reviewer
        $response = $this->actingAs($admin)->postJson("/api/papers/{$paperId}/assign-reviewer", [
            'reviewer_id' => $reviewer->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('under_review', $response->json('status'));
        $this->assertEquals($reviewer->id, $response->json('assigned_reviewer_id'));

        // 4. Reviewer submits review (Accept)
        $response = $this->actingAs($reviewer)->postJson("/api/reviews", [
            'paper_id' => $paperId,
            'comment' => 'Great paper, accepted.',
            'decision' => 'accept',
        ]);

        $response->assertStatus(201);

        // Verify paper status is updated to accepted
        $paper = Paper::find($paperId);
        $this->assertEquals('accepted', $paper->status);
    }
}
