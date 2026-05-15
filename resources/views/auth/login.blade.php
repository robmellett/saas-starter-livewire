<x-layouts.guest :heading="'Sign in to your account'">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required autofocus
                   value="{{ old('email') }}"
                   class="mt-1 block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="mt-1 block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2">
                <input name="remember" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-700">
                <span>Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Forgot password?</a>
        </div>

        <button type="submit" class="w-full rounded-md bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white py-2 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-white">
            Sign in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Sign up</a>
    </p>
</x-layouts.guest>
