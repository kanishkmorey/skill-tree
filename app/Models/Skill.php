<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'notes', 'workspace_id', 'created_by'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use BelongsToWorkspace, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Skill $skill): void {
            $skill->slug ??= Str::slug($skill->name);
        });
    }

    /**
     * @return HasMany<Requirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    /**
     * @return HasMany<SkillKnowledge, $this>
     */
    public function knowledge(): HasMany
    {
        return $this->hasMany(SkillKnowledge::class);
    }

    /**
     * @return BelongsToMany<resource, $this>
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'skill_resources');
    }

    /**
     * @return HasMany<TreeNode, $this>
     */
    public function treeNodes(): HasMany
    {
        return $this->hasMany(TreeNode::class);
    }
}
