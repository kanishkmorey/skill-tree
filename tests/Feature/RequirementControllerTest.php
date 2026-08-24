<?php

use App\Models\Skill;

test('lists, creates, updates and deletes requirements for a skill', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $this->getJson("/api/v1/skills/{$skill->id}/requirements", $headers)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $store = $this->postJson("/api/v1/skills/{$skill->id}/requirements", [
        'title' => 'Write 500 lines of Rust',
    ], $headers)->assertCreated();

    $requirementId = $store->json('data.id');

    $this->assertDatabaseHas('requirements', [
        'id' => $requirementId,
        'skill_id' => $skill->id,
        'fulfilled' => false,
    ]);

    $this->patchJson("/api/v1/requirements/{$requirementId}", ['fulfilled' => true], $headers)
        ->assertOk()
        ->assertJsonPath('data.fulfilled', true);

    $this->deleteJson("/api/v1/requirements/{$requirementId}", [], $headers)->assertNoContent();

    $this->assertDatabaseMissing('requirements', ['id' => $requirementId]);
});

test('cannot manage requirements through a skill in another workspace', function () {
    $skill = Skill::factory()->create(['workspace_id' => 111]);
    $otherHeaders = actingAsWorkspace(workspaceId: 222);

    $this->getJson("/api/v1/skills/{$skill->id}/requirements", $otherHeaders)->assertNotFound();
});
