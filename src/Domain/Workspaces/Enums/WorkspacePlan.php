<?php

namespace Domain\Workspaces\Enums;

enum WorkspacePlan: string
{
    case Free = 'free';
    case Premium = 'premium';
    case Enterprise = 'enterprise';

    public function isPaid(): bool
    {
        return $this !== self::Free;
    }
}
