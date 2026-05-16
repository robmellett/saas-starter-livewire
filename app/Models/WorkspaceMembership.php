<?php

namespace App\Models;

use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property WorkspaceRole $role
 * @property int $user_id
 * @property int $workspace_id
 */
class WorkspaceMembership extends Pivot
{
    protected $table = 'workspace_user';

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }
}
