<x-layouts.guest
    :heading="'Forgot your password?'"
    :subheading="'Enter your email and we\'ll send you a reset link.'">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required autofocus
                   value="{{ old('email') }}"
                   class="mt-1 block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-white py-2 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-white">
            Send reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Back to sign in</a>
    </p>
</x-layouts.guest>
