<?php

declare(strict_types=1);

namespace Domain\Workspaces\Policies;

use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Enums\WorkspaceRole;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->roleFor($user, $workspace) !== null;
    }

    public function manageBilling(User $user, Workspace $workspace): bool
    {
        return $this->roleFor($user, $workspace) === WorkspaceRole::Owner;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        $role = $this->roleFor($user, $workspace);

        return $role !== null && $role->canManageMembers();
    }

    private function roleFor(User $user, Workspace $workspace): ?WorkspaceRole
    {
        $member = $workspace
            ->members()
            ->where('users.id', $user->id)
            ->first();

        return $member?->pivot->role;
    }
}
