# markymark.io (Laravel)

This project's conventions **override** the Cloudflare Workers stack documented in `~/.claude/CLAUDE.md`. None of that applies here — this is a PHP/Laravel app.

## Stack

- **PHP**: `^8.3` (local: 8.4)
- **Laravel**: `^13.8` (currently 13.9)
- **Frontend**: Vite 8 + TailwindCSS 4 + `laravel-vite-plugin`
- **Database**: SQLite (`database/database.sqlite`) by default
- **Testing**: PHPUnit 12 (Pest plugin is allow-listed in composer but not installed)
- **Linter**: Laravel Pint (preset: `laravel`)
- **Logs**: `laravel/pail` for tailing
- **Package manager**: `npm` (matches `composer.json` scripts and `.npmrc`). Do **not** switch to pnpm without asking.

## Commands

- `composer dev` — runs `php artisan serve`, queue listener, `pail`, and `npm run dev` concurrently
- `composer test` — clears config and runs `php artisan test`
- `composer setup` — full install + key gen + migrate + npm build (post-clone bootstrap)
- `php artisan migrate` — apply migrations (SQLite local)
- `vendor/bin/pint` — format code

## Defaults already wired in

- `Model::shouldBeStrict()` is on in non-production (`AppServiceProvider::boot`). This means lazy loading, missing attribute access, and silently-discarded attributes all throw — fix at the call site, don't disable.
- `DB::prohibitDestructiveCommands()` is on in production. `migrate:fresh`, `db:wipe`, etc. will refuse to run when `APP_ENV=production`.

## House rules (apply Laravel best-practices skill)

The `laravel-best-practices` skill is the authoritative source — invoke it for any non-trivial change. Highlights specific to how this project should be developed:

- **Form Requests** for validation, never `$request->all()`
- **Eloquent eager-loading** (`with()`, `withCount()`) — strict mode will throw on N+1
- **Action classes** for business logic (`app/Actions/`); keep controllers under 10 lines
- **Migrations are immutable** once applied to production — write a new migration to fix
- **`@csrf` on every state-changing form**; policies/gates for authorization
- **No raw SQL with user input** — Eloquent or query builder bindings
- Tests use `LazilyRefreshDatabase`; in-memory SQLite is configured in `phpunit.xml`

## What to ask before doing

- Switching the database (SQLite → MySQL/Postgres)
- Adding Pest on top of PHPUnit (the allow-list is there, but it's not installed)
- Adding Telescope/Pulse/Horizon (the test env disables them — they may be added later)
- Adding Inertia/Livewire/Filament (none are present; the stack is plain Blade + Vite right now)
