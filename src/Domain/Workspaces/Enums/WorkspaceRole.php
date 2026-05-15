<?php

namespace Domain\Workspaces\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    public function canManageMembers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
