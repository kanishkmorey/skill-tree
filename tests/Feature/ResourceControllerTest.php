<?php

use App\Models\Resource;
use App\Models\Skill;

test('creates, lists, updates and deletes resources', function () {
    $headers = actingAsWorkspace(workspaceId: 111);

    $store = $this->postJson('/api/v1/resources', [
        'name' => 'The Rust Book',
        'type' => 'book',
        'url' => 'https://doc.rust-lang.org/book/',
    ], $headers)->assertCreated();

    $resourceId = $store->json('data.id');

    $this->getJson('/api/v1/resources', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->patchJson("/api/v1/resources/{$resourceId}", ['status' => 'in-progress'], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'in-progress');

    $this->deleteJson("/api/v1/resources/{$resourceId}", [], $headers)->assertNoContent();
});

test('attaches and detaches a resource from a skill', function () {
    $headers = actingAsWorkspace(workspaceId: 111);
    $skill = Skill::factory()->create(['workspace_id' => 111]);
    $resource = Resource::factory()->create(['workspace_id' => 111]);

    $this->postJson("/api/v1/skills/{$skill->id}/resources", [
        'resource_id' => $resource->id,
    ], $headers)->assertOk();

    $this->assertDatabaseHas('skill_resources', [
        'skill_id' => $skill->id,
        'resource_id' => $resource->id,
    ]);

    $this->deleteJson("/api/v1/skills/{$skill->id}/resources/{$resource->id}", [], $headers)
        ->assertNoContent();

    $this->assertDatabaseMissing('skill_resources', [
        'skill_id' => $skill->id,
        'resource_id' => $resource->id,
    ]);
});
