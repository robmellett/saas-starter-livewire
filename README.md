# markymark.io

A Laravel 13 + Livewire 4 app with workspace-scoped Paddle billing.

## Stack

- **PHP** 8.3+ (developed on 8.4) · **Laravel** 13 · **Livewire** 4
- **DB** PostgreSQL via Laravel Sail in dev; SQLite in-memory for tests
- **Frontend** Vite 8 + TailwindCSS 4 + plain Blade (no Inertia / Filament)
- **Auth** Laravel Fortify (custom Blade views, no email verification, 2FA columns present but no UI)
- **Billing** Laravel Cashier Paddle (Paddle Billing — current product, not Classic)
- **DTOs** spatie/laravel-data
- **Quality** Pint, Larastan, PHPUnit 12

## Architecture (Laravel Beyond CRUD)

Domain logic lives outside `app/` in a `src/Domain/<BoundedContext>/` tree. Framework wiring (controllers, providers, middleware) stays in `app/`. Eloquent models stay in `app/Models/`.

```
app/
├── Http/Middleware/         RequiresPlan
├── Livewire/Billing/        PlanPicker, SubscriptionPanel
├── Models/                  User, Workspace
└── Providers/               AppServiceProvider, FortifyServiceProvider
src/Domain/
├── Billing/
│   ├── Actions/             StartCheckoutAction
│   ├── Data/                PlanData (DTO from config/billing.php)
│   └── Listeners/           SyncSubscriptionPlan
├── Users/
│   ├── Actions/             CreateNewUser, ResetUserPassword, UpdateUserPassword,
│   │                        UpdateUserProfileInformation, PasswordValidationRules
│   └── Data/                RegisterUserData
└── Workspaces/
    ├── Actions/             CreatePersonalWorkspaceAction
    ├── Enums/               WorkspacePlan, WorkspaceRole
    └── Policies/            WorkspacePolicy
```

Composer autoload: `App\\: app/`, `Domain\\: src/Domain/`, `Support\\: src/Support/`.

## Setup

```bash
git clone <repo>
cd markymark.io-laravel

cp .env.example .env
# Fill in PADDLE_* vars (see Billing section)

composer install
npm install
php artisan key:generate

./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run dev   # in another terminal
```

Open <http://localhost> · Mailpit (password-reset emails) at <http://localhost:8025>.

## Routes

| Method | Path | Name | Notes |
|---|---|---|---|
| GET | `/` | — | Welcome page |
| GET | `/up` | — | Laravel health check |
| **Auth (Fortify)** | | | |
| GET | `/login` | `login` | Custom Blade view |
| POST | `/login` | `login.store` | |
| POST | `/logout` | `logout` | |
| GET | `/register` | `register` | Creates user + personal workspace |
| POST | `/register` | `register.store` | |
| GET | `/forgot-password` | `password.request` | |
| POST | `/forgot-password` | `password.email` | Sends reset email |
| GET | `/reset-password/{token}` | `password.reset` | |
| POST | `/reset-password` | `password.update` | |
| PUT | `/user/profile-information` | `user-profile-information.update` | |
| PUT | `/user/password` | `user-password.update` | |
| GET / POST | `/user/confirm-password` | `password.confirm[.store]` | |
| **2FA (routes exist, no UI yet)** | | | |
| POST / DELETE | `/user/two-factor-authentication` | `two-factor.enable/disable` | |
| GET | `/two-factor-challenge` | `two-factor.login` | |
| **App** | | | |
| GET | `/dashboard` | `dashboard` | Auth required; shows current workspace |
| GET | `/billing` | `billing` | Auth required; PlanPicker + SubscriptionPanel |
| **Billing webhooks** | | | |
| POST | `/paddle/webhook` | `cashier.webhook` | Paddle webhook receiver; signature verified by Cashier |
| **Livewire internals** | | | |
| POST | `/livewire-*/update` | `default-livewire.update` | Component hydration |
| various | `/livewire-*/...` | — | Asset / file upload endpoints |

Run `./vendor/bin/sail artisan route:list` for the live list.

## How auth works

Fortify owns the routes; we override behavior via Action classes and view callbacks.

1. **`Domain\Users\Actions\CreateNewUser`** is bound to Fortify's `CreatesNewUsers` contract in `App\Providers\FortifyServiceProvider::boot()`. It validates input through `Domain\Users\Data\RegisterUserData` (Spatie laravel-data with rules attached to the DTO), creates the `User`, and within the same DB transaction invokes `Domain\Workspaces\Actions\CreatePersonalWorkspaceAction` which:
   - Creates a `Workspace` named e.g. `"Mark's Workspace"`
   - Attaches the user to the `workspace_user` pivot with role `owner`
   - Sets `users.current_workspace_id` to the new workspace
2. Login / logout / forgot-password / reset are all stock Fortify routes; we provide the views via `Fortify::loginView(fn () => view('auth.login'))` etc.
3. Email verification is **disabled** in `config/fortify.php`. 2FA columns exist on `users` but no UI ships yet — flip features off if you don't want the routes exposed.

Layouts live as anonymous Blade components in `resources/views/components/layouts/`:
- `guest.blade.php` — unauthenticated screens
- `app.blade.php` — authenticated screens (includes `@livewireStyles`, `@livewireScripts`, `@stack('head')`, `@stack('scripts')`)

## How billing works

**Tenant model**: subscriptions belong to a `Workspace`, not a `User`. The `Laravel\Paddle\Billable` trait is on `App\Models\Workspace`.

**Plans** are configured in `config/billing.php`:

```
Free       — no Paddle price; default
Premium    — env('PADDLE_PRICE_PREMIUM')
Enterprise — env('PADDLE_PRICE_ENTERPRISE')
```

`Domain\Billing\Data\PlanData::catalog()` returns all three as DTOs. `PlanData::fromKey('premium')` returns one.

**Checkout flow** (Livewire + Paddle.js overlay):

1. User on `/billing` sees `<livewire:billing.plan-picker />`
2. Clicks "Upgrade to Premium" → fires `wire:click="subscribe('premium')"` on `App\Livewire\Billing\PlanPicker`
3. Component authorizes (`manageBilling` policy = Owner only), calls `Domain\Billing\Actions\StartCheckoutAction` which builds a `Laravel\Paddle\Checkout` via `$workspace->subscribe($priceId)` (creating the Paddle customer record on first use; idempotent thereafter)
4. Component dispatches a browser event `paddle-checkout` with the checkout config
5. JS handler in `resources/views/billing/index.blade.php` calls `Paddle.Checkout.open(config)`, the Paddle overlay opens
6. User completes payment in the overlay
7. Paddle sends webhooks to `/paddle/webhook`
8. Cashier creates `subscriptions` + `subscription_items` rows, dispatches `Laravel\Paddle\Events\SubscriptionCreated`
9. `Domain\Billing\Listeners\SyncSubscriptionPlan` writes `workspaces.plan` from the subscription's price ID

The Paddle.js script is injected by `@paddleJS` (a Cashier directive) on the `/billing` page only — we don't load it globally.

**Reading the current plan**: always call `$workspace->currentPlan()`, never the raw `plan` column.

```php
public function currentPlan(): WorkspacePlan
{
    if (! $this->subscribed()) {
        return WorkspacePlan::Free;          // also handles post-grace
    }
    if ($this->subscription()?->hasPrice($enterprisePriceId)) return WorkspacePlan::Enterprise;
    
    if ($this->subscription()?->hasPrice($premiumPriceId))    return WorkspacePlan::Premium;
    
    return $this->plan ?? WorkspacePlan::Free;
}
```

The `workspaces.plan` column is a denormalized cache, useful for `WHERE plan = 'premium'` queries. **Don't authorize off it** — Cashier's `subscribed()` is the source of truth, and `currentPlan()` wraps it.

**Cancellation**: `SubscriptionPanel::cancel()` calls `$subscription->cancel()`. Paddle keeps the subscription active until `ends_at`. The listener does NOT subscribe to `SubscriptionCanceled`, so `workspaces.plan` stays on the paid tier for the read-side cache. Once the grace period elapses, Cashier's `subscribed()` returns false → `currentPlan()` returns Free. No scheduled job needed.

**Resume**: during grace period, `SubscriptionPanel::resume()` calls `$subscription->resume()`.

**Update payment method**: link to `$subscription->paymentMethodUpdateUrl()` (Paddle-hosted page).

### Required env vars

```
PADDLE_SANDBOX=true               # false for production
PADDLE_SELLER_ID=
PADDLE_API_KEY=
PADDLE_CLIENT_SIDE_TOKEN=         # used by Paddle.js
PADDLE_WEBHOOK_SECRET=
PADDLE_PRICE_PREMIUM=pri_xxx
PADDLE_PRICE_ENTERPRISE=pri_xxx
```

To test webhooks locally, expose the app to the public internet (e.g. `cloudflared tunnel --url http://localhost`) and register the tunnel URL + `/paddle/webhook` in Paddle's dashboard.

## Authorization

`Domain\Workspaces\Policies\WorkspacePolicy` is auto-discovered via `#[UsePolicy]` on `Workspace`:

```php
$user->can('view', $workspace)            // member?
$user->can('manageBilling', $workspace)   // owner only
$user->can('manageMembers', $workspace)   // owner or admin
```

Roles are stored on the `workspace_user` pivot's `role` column as `WorkspaceRole` enum values.

## Plan gating

Route middleware: `plan:<csv>` (alias for `App\Http\Middleware\RequiresPlan`).

```php
Route::middleware(['auth', 'plan:premium,enterprise'])->group(function () {
    // routes only available to paid workspaces
});

Route::middleware(['auth', 'plan:enterprise'])->group(function () {
    // enterprise-only
});
```

The middleware reads `$user->currentWorkspace->currentPlan()`, so grace-period downgrades happen automatically.

## Quality / dev defaults

`AppServiceProvider::boot()` wires:
- `Model::shouldBeStrict()` in non-production — lazy loading, missing attributes, and silently-discarded attributes all throw. Fix the call site rather than relax the rule.
- `DB::prohibitDestructiveCommands()` in production — blocks `migrate:fresh`, `db:wipe`, etc.
- `Factory::guessFactoryNamesUsing()` — keeps factory resolution working as we add more models.

## Testing

```bash
./vendor/bin/sail artisan test
```

23 feature tests covering registration + workspace bootstrap, login, password reset, dashboard auth gate, billing page render, plan middleware, and the workspace policy. Tests use `LazilyRefreshDatabase` against the Sail Postgres container — each test runs in a transaction so data doesn't leak.

Gaps to add when you next touch billing:
- End-to-end checkout / cancel / resume via `Cashier::fake()`
- `SyncSubscriptionPlan` listener test (needs a seeded `Subscription` + `SubscriptionItem`)
- Webhook signature verification with a recorded Paddle payload

## Commands

```bash
./vendor/bin/sail up -d                   # start containers
./vendor/bin/sail artisan migrate         # apply migrations
./vendor/bin/sail artisan test            # run the suite
./vendor/bin/sail artisan tinker          # REPL
./vendor/bin/pint                         # format
./vendor/bin/phpstan analyse              # static analysis
npm run dev                               # Vite dev server (host-side)
npm run build                             # production bundle
```

## License

MIT.
