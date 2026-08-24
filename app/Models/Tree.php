<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\TreeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'workspace_id', 'created_by'])]
class Tree extends Model
{
    /** @use HasFactory<TreeFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * @return HasMany<TreeNode, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(TreeNode::class);
    }
}
