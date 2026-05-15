<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Domain\Workspaces\Enums\WorkspacePlan;
use Domain\Workspaces\Enums\WorkspaceRole;
use Domain\Workspaces\Policies\WorkspacePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Paddle\Billable;

#[Fillable(['name', 'plan'])]
#[UsePolicy(WorkspacePolicy::class)]
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

    public function currentPlan(): WorkspacePlan
    {
        if (! $this->subscribed()) {
            return WorkspacePlan::Free;
        }

        $subscription = $this->subscription();

        $enterprise = config('billing.plans.enterprise.price_id');
        if ($enterprise && $subscription?->hasPrice($enterprise)) {
            return WorkspacePlan::Enterprise;
        }

        $premium = config('billing.plans.premium.price_id');
        if ($premium && $subscription?->hasPrice($premium)) {
            return WorkspacePlan::Premium;
        }

        return $this->plan ?? WorkspacePlan::Free;
    }
}
