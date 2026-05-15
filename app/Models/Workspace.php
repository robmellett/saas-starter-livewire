<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Domain\Workspaces\Enums\WorkspacePlan;
use Domain\Workspaces\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Paddle\Billable;

#[Fillable(['name', 'plan'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use Billable, HasFactory;

    protected function casts(): array
    {
        return [
            'plan' => WorkspacePlan::class,
        ];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function owner(): ?User
    {
        /** @var User|null */
        return $this->members()
            ->wherePivot('role', WorkspaceRole::Owner->value)
            ->first();
    }
}
