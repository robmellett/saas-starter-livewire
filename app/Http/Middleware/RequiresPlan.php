<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Domain\Workspaces\Enums\WorkspacePlan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresPlan
{
    public function handle(Request $request, Closure $next, string ...$plans): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $workspace = $user->currentWorkspace;

        abort_unless($workspace !== null, 403, 'No workspace selected.');

        $current = $workspace->currentPlan();

        $allowed = array_map(
            fn (string $plan) => WorkspacePlan::from($plan),
            $plans,
        );

        if (! in_array($current, $allowed, true)) {
            abort(403, 'This area requires plan(s): '.implode(', ', $plans).". Current plan: {$current->value}.");
        }

        return $next($request);
    }
}
