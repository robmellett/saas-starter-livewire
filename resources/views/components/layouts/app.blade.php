<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="h-full font-sans antialiased text-zinc-900 dark:text-zinc-100">
    <nav class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a href="{{ url('/dashboard') }}" class="text-base font-semibold tracking-tight">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                    @auth
                        <div class="flex items-center gap-1">
                            <a href="{{ route('dashboard') }}"
                               @class([
                                   'rounded-md px-3 py-1.5 text-sm font-medium',
                                   'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' => request()->routeIs('dashboard'),
                                   'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800' => ! request()->routeIs('dashboard'),
                               ])>
                                Dashboard
                            </a>
                            <a href="{{ route('billing') }}"
                               @class([
                                   'rounded-md px-3 py-1.5 text-sm font-medium',
                                   'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' => request()->routeIs('billing*'),
                                   'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800' => ! request()->routeIs('billing*'),
                               ])>
                                Billing
                            </a>
                        </div>
                    @endauth
                </div>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <span class="text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Log out</button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
