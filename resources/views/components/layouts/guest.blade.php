<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-zinc-900 dark:text-zinc-100">
    <div class="min-h-full flex flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <a href="{{ url('/') }}" class="block text-center text-xl font-semibold tracking-tight">
                {{ config('app.name', 'Laravel') }}
            </a>
            @isset($heading)
                <h1 class="mt-6 text-center text-2xl font-bold tracking-tight">{{ $heading }}</h1>
            @endisset
            @isset($subheading)
                <p class="mt-2 text-center text-sm text-zinc-600 dark:text-zinc-400">{{ $subheading }}</p>
            @endisset
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-950/30 p-3 text-sm text-green-700 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</body>
</html>
