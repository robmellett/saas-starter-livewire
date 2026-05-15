# markymark.io (Laravel)

This project's conventions **override** the Cloudflare Workers stack documented in `~/.claude/CLAUDE.md`. None of that applies here — this is a PHP/Laravel app.

## Stack

- **PHP**: `^8.3` (local: 8.4)
- **Laravel**: `^13.8` (currently 13.9)
- **Frontend**: Vite 8 + TailwindCSS 4 + `laravel-vite-plugin`
- **Database**: PostgreSQL (via Sail in dev; `compose.yaml`). SQLite in-memory for tests (see `phpunit.xml`).
- **Testing**: PHPUnit 12 (Pest plugin is allow-listed in composer but not installed)
- **Linter**: Laravel Pint (preset: `laravel`)
- **Logs**: `laravel/pail` for tailing
- **Package manager**: `npm` (matches `composer.json` scripts and `.npmrc`). Do **not** switch to pnpm without asking.

## Commands

- `./vendor/bin/sail up -d` — start dev containers (app, pgsql, redis, mailpit)
- `./vendor/bin/sail artisan ...` — run artisan inside the app container (always use this, not bare `php artisan`, since the DB host is `pgsql` inside Docker)
- `./vendor/bin/sail artisan test` — run the test suite
- `./vendor/bin/sail composer ...` — composer inside the container
- `npm run dev` / `npm run build` — Vite (runs on host, not Sail)
- `vendor/bin/pint` — format code (host-side; reads files only)
- Mailpit UI: http://localhost:8025 — preview password-reset emails locally

## Defaults already wired in

- `Model::shouldBeStrict()` is on in non-production (`AppServiceProvider::boot`). This means lazy loading, missing attribute access, and silently-discarded attributes all throw — fix at the call site, don't disable.
- `DB::prohibitDestructiveCommands()` is on in production. `migrate:fresh`, `db:wipe`, etc. will refuse to run when `APP_ENV=production`.

## Architecture: Laravel Beyond CRUD (Spatie / Roose)

The codebase follows Spatie's DDD-style **Laravel Beyond CRUD** layout. Domain code is the source of truth; `app/` is just framework wiring.

### Layout

```
app/                          # Application layer (HTTP/CLI wiring) — namespace App\
  Http/Controllers/           # Thin controllers (<10 lines), defer to actions
  Providers/
src/
  Domain/<BoundedContext>/    # Domain layer — namespace Domain\<BoundedContext>\
    Actions/                  # Single-purpose invokables (the unit of business logic)
    Data/                     # spatie/laravel-data DTOs (inputs, outputs, internal value objects)
    States/                   # spatie/laravel-model-states (when needed)
    QueryBuilders/            # Custom query objects for non-trivial reads
    Events/ Listeners/ Notifications/
  Support/                    # Cross-domain utilities — namespace Support\
```

Composer autoload (already wired): `App\\: app/`, `Domain\\: src/Domain/`, `Support\\: src/Support/`.

### Rules of thumb

- **Controllers** belong in `app/Http/Controllers/`. They are thin: resolve a Form Request or `Data` object, hand off to an Action, return a response. Keep under 10 lines.
- **Actions** are single-purpose invokable classes in `src/Domain/<Ctx>/Actions/`. They contain the actual business logic. Inject dependencies via the constructor; never `app()->make()` inside.
- **DTOs via `spatie/laravel-data`** are preferred over plain Form Requests for anything that flows past the HTTP layer. Use `Data` objects as Action inputs, internal value objects, and resource outputs. Form Requests are fine for simple validation that doesn't propagate further.
- **Models** live in `app/Models/`.
- **No `Service` classes.** If you reach for one, write an Action instead. Multiple related Actions sharing helpers? Extract to a value object in `Data/` or a query builder in `QueryBuilders/`.
- **Cross-domain calls** go through events or explicit Action-to-Action invocation. No reaching into another domain's models/queries directly.

### Spatie packages in play

- `spatie/laravel-data` — DTOs, request validation, resource transformation (installed)
- `spatie/laravel-model-states` — add when a model has a state machine (not installed yet)
- `spatie/laravel-query-builder` — add for filterable/sortable list endpoints (not installed yet)
- `spatie/laravel-permission` — add for roles/permissions (not installed yet)
- `spatie/laravel-ray` — dev-only debugging (installed)

## House rules (apply Laravel best-practices skill)

The `laravel-best-practices` skill is the authoritative source for general Laravel patterns — invoke it for any non-trivial change. Beyond CRUD layering is layered on top:

- **DTOs (`Data`) over Form Requests** wherever the validated input becomes a value object or crosses the controller boundary
- **Eloquent eager-loading** (`with()`, `withCount()`) — strict mode (`Model::shouldBeStrict`) will throw on N+1 in dev
- **Actions, not services**; keep controllers under 10 lines
- **Migrations are immutable** once applied to production — write a new migration to fix
- **`@csrf` on every state-changing form**; policies/gates for authorization (policies live with the model in `src/Domain/<Ctx>/Policies/`)
- **No raw SQL with user input** — Eloquent or query builder bindings
- Tests use `LazilyRefreshDatabase`; in-memory SQLite is configured in `phpunit.xml`
- Static analysis via Larastan: `vendor/bin/phpstan analyse`

## What to ask before doing

- **Creating a new bounded context** — confirm the name and scope before scaffolding `src/Domain/<X>/`
- **Moving the existing `app/Models/User.php`** to `src/Domain/Users/Models/User.php` — this needs `config/auth.php` updated and factory resolution rewired
- **Replacing `app/` with `src/App/` entirely** — a bigger move, deferred until there's a reason
- Switching the database (SQLite → MySQL/Postgres)
- Adding Pest on top of PHPUnit (allow-listed in composer but not installed)
- Adding Telescope/Pulse/Horizon (the test env disables them — they may be added later)
- Adding Inertia/Livewire/Filament (none are present; the stack is plain Blade + Vite right now)
