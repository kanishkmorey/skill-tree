<?php

use App\Models\Skill;

test('lists, creates, updates and deletes knowledge entries for a skill', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $store = $this->postJson("/api/v1/skills/{$skill->id}/knowledge", [
        'title' => 'Ownership and borrowing',
        'status' => 'learning',
    ], $headers)->assertCreated();

    $knowledgeId = $store->json('data.id');

    $this->getJson("/api/v1/skills/{$skill->id}/knowledge", $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->patchJson("/api/v1/knowledge/{$knowledgeId}", ['status' => 'proficient'], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'proficient');

    $this->deleteJson("/api/v1/knowledge/{$knowledgeId}", [], $headers)->assertNoContent();

    $this->assertDatabaseMissing('skill_knowledge', ['id' => $knowledgeId]);
});

test('rejects an invalid knowledge status', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);

    $this->postJson("/api/v1/skills/{$skill->id}/knowledge", [
        'title' => 'Ownership and borrowing',
        'status' => 'expert',
    ], $headers)->assertUnprocessable();
});
