<?php

namespace Domain\Workspaces\Actions;

use App\Models\User;
use App\Models\Workspace;
use Domain\Workspaces\Enums\WorkspacePlan;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Support\Facades\DB;

class CreatePersonalWorkspaceAction
{
    public function execute(User $user): Workspace
    {
        return DB::transaction(function () use ($user) {
            $workspace = Workspace::create([
                'name' => $this->personalWorkspaceName($user),
                'plan' => WorkspacePlan::Free,
            ]);

            $workspace->members()->attach($user->id, [
                'role' => WorkspaceRole::Owner->value,
            ]);

            $user->forceFill(['current_workspace_id' => $workspace->id])->save();

            return $workspace;
        });
    }

    private function personalWorkspaceName(User $user): string
    {
        $firstName = trim(explode(' ', $user->name)[0]);

        return $firstName !== '' ? "{$firstName}'s Workspace" : 'My Workspace';
    }
}
