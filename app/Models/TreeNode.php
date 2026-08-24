<?php

namespace App\Models;

use Database\Factories\TreeNodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tree_id', 'skill_id', 'parent_node_id'])]
class TreeNode extends Model
{
    /** @use HasFactory<TreeNodeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Tree, $this>
     */
    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    /**
     * @return BelongsTo<Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * @return BelongsTo<TreeNode, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TreeNode::class, 'parent_node_id');
    }

    /**
     * @return HasMany<TreeNode, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(TreeNode::class, 'parent_node_id');
    }
}
