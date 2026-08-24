<?php

use App\Models\Skill;

test('creates a skill scoped to the current workspace with an auto-generated slug', function () {
    $headers = actingAsWorkspace(workspaceId: 111, userId: 166503);

    $response = $this->postJson('/api/v1/skills', [
        'name' => 'Deep Work',
        'description' => 'Focused, undistracted work.',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Deep Work')
        ->assertJsonPath('data.slug', 'deep-work')
        ->assertJsonPath('data.created_by', 166503);

    $this->assertDatabaseHas('skills', [
        'name' => 'Deep Work',
        'slug' => 'deep-work',
        'workspace_id' => 111,
        'created_by' => 166503,
    ]);
});

test('rejects a skill without a name', function () {
    $headers = actingAsWorkspace();

    $this->postJson('/api/v1/skills', [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('only lists skills belonging to the current workspace', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $this->postJson('/api/v1/skills', ['name' => 'Workspace One Skill'], $headers)->assertCreated();

    $otherHeaders = actingAsWorkspace(workspaceId: 222);
    $this->postJson('/api/v1/skills', ['name' => 'Workspace Two Skill'], $otherHeaders)->assertCreated();

    $this->getJson('/api/v1/skills', $otherHeaders)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Workspace Two Skill');
});

test('cannot view a skill belonging to another workspace', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $otherHeaders = actingAsWorkspace(workspaceId: 222);

    $this->getJson("/api/v1/skills/{$skill->id}", $otherHeaders)->assertNotFound();
    $this->getJson("/api/v1/skills/{$skill->id}", $headers)->assertOk();
});

test('updates a skill', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $this->patchJson("/api/v1/skills/{$skill->id}", ['description' => 'Updated'], $headers)
        ->assertOk()
        ->assertJsonPath('data.description', 'Updated');
});

test('deletes a skill', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $this->deleteJson("/api/v1/skills/{$skill->id}", [], $headers)->assertNoContent();

    $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
});
