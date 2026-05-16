<?php

namespace Domain\Workspaces\Enums;

enum WorkspacePlan: string
{
    case Free = 'free';

    case Basic = 'basic';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public function isPaid(): bool
    {
        return $this !== self::Free;
    }
}
