<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreeNodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tree_id' => $this->tree_id,
            'parent_node_id' => $this->parent_node_id,
            'skill' => new SkillResource($this->whenLoaded('skill')),
            'children' => self::collection($this->children ?? []),
        ];
    }
}
