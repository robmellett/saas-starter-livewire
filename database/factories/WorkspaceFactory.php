<?php

namespace Database\Factories;

use Domain\Workspaces\Enums\WorkspacePlan;
use Domain\Workspaces\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'plan' => WorkspacePlan::Free,
        ];
    }

    public function premium(): static
    {
        return $this->state(['plan' => WorkspacePlan::Premium]);
    }

    public function enterprise(): static
    {
        return $this->state(['plan' => WorkspacePlan::Enterprise]);
    }
}
