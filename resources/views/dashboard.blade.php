<x-layouts.app :title="'Dashboard'">
    @php($workspace = auth()->user()->currentWorkspace)
    <h1 class="text-xl font-semibold tracking-tight">Dashboard</h1>
    @if ($workspace)
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Workspace: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $workspace->name }}</span>
            &middot;
            Plan: <span class="font-medium uppercase tracking-wide">{{ $workspace->plan->value }}</span>
        </p>
    @else
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">No workspace selected.</p>
    @endif
</x-layouts.app>
