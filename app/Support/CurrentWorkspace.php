<?php

namespace App\Support;

class CurrentWorkspace
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly int $userId,
    ) {}
}
