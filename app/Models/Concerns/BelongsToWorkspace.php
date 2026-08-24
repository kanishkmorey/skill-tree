<?php

namespace App\Models\Concerns;

use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(function (Builder $builder): void {
            if (app()->bound(CurrentWorkspace::class)) {
                $builder->where($builder->getModel()->getTable().'.workspace_id', app(CurrentWorkspace::class)->workspaceId);
            }
        });

        static::creating(function ($model): void {
            if ($model->workspace_id !== null && $model->created_by !== null) {
                return;
            }

            $workspace = app(CurrentWorkspace::class);

            $model->workspace_id ??= $workspace->workspaceId;
            $model->created_by ??= $workspace->userId;
        });
    }
}
