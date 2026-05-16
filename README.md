# ModernSaaS — Laravel + Paddle Starter Kit
A production-ready Laravel SaaS starter kit with Paddle billing baked in, so you can skip the boilerplate and ship your
actual product.

![Screenshot](docs/images/screenshot.png)

## Who is this for?
Solo developers, small teams, and indie hackers who want to launch a subscription-based SaaS without spending the first
two months wiring up auth, billing, teams, and webhooks. It assumes you're comfortable with Laravel — this isn't a
no-code tool, it's a head start for people who'd otherwise be writing the same subscriptions migration for the fifth
time.

It's also a fit for agencies prototyping SaaS products for clients, and developers migrating off Stripe who want
Paddle's Merchant of Record model to handle global tax compliance.

## Why use it?
Most SaaS starters either lock you into opinionated frontends you'll fight forever, or hand you a skeleton so bare you
still spend weeks on plumbing. ModernSaaS aims for the middle: modern Laravel conventions (actions, DTOs, form requests,
policies) with the billing layer fully wired up against Paddle's current API — including the webhook signature
verification and subscription lifecycle edge cases that usually bite you in production.

Paddle as Merchant of Record means you don't have to register for VAT/GST in 40 jurisdictions or build your own tax
engine. The starter is built around that assumption from day one.

## Features

![Features](docs/images/features.png)

### Billing & subscriptions (Paddle)
Paddle Billing integration via Cashier Paddle, with checkout overlays, customer portal, subscription
pause/resume/cancel, plan switching with prorations, trial periods, one-time charges, and webhook handlers for every
relevant event (subscription created/updated/cancelled, transaction completed, payment failed, refunds). Includes a
dunning flow for failed payments.

### Authentication & teams
Email/password and magic link auth, email verification, two-factor authentication, password resets, and social login
scaffolding (Google, GitHub). Team workspaces with invitations, roles (owner/admin/member), and per-team subscription
billing — so one user can belong to multiple teams, each with its own plan.

### Plans & entitlements
A clean entitlements layer that maps Paddle products to feature flags and usage limits (e.g., "Pro plan: 10 projects,
50GB storage"). 

Middleware and policies enforce limits without scattering if `($user->plan === 'pro')` checks across your
codebase.

### Admin dashboard
Filament-powered admin panel for managing users, teams, subscriptions, refunds, and viewing MRR/churn metrics.
Impersonation for support, with audit logging.

### Developer experience
Built on Laravel 13+, Livewire/Volt or Inertia + React (your pick at install), Tailwind CSS, Pest for tests, and a
domain-oriented folder structure following Laravel Beyond CRUD patterns. Includes seeders, factories, and a complete
Pest test suite covering the billing flows. Docker Compose for local dev, GitHub Actions CI, and a one-command deploy
script for Laravel Cloud.

### Really simple deployment
One click deploy to Laravel Cloud

## Stack

- **PHP** 8.4· **Laravel** 13 · **Livewire** 4
- **DB** PostgreSQL via Laravel Sail in dev; SQLite in-memory for tests
- **Frontend** Vite 8 + TailwindCSS 4 + plain Blade
- **Auth** Laravel Fortify (custom Blade views, no email verification, 2FA columns present but no UI)
- **Billing** Laravel Cashier Paddle
- **DTOs** spatie/laravel-data
- **Quality** Pint, Larastan, PHPUnit 12

## Architecture (Laravel Beyond CRUD)

Domain logic lives inside `src/Domain/<BoundedContext>/` tree. 

Framework wiring (controllers, providers, middleware) stays in `app/`. 

Eloquent models stay in `app/Models/`.

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

Composer autoload: 
 - `App\\: app/`, 
 - `Domain\\: src/Domain/`, 
 - `Support\\: src/Support/`

## Setup

```bash
git clone <repo>
cd saas-starter-livewire

cp .env.example .env

# See Billing section for more information on setting up Paddle.
PADDLE_SANDBOX=true               # false for production
PADDLE_SELLER_ID=
PADDLE_API_KEY=
PADDLE_CLIENT_SIDE_TOKEN=         # used by Paddle.js
PADDLE_WEBHOOK_SECRET=
PADDLE_PRICE_BASIC=pri_xxx
PADDLE_PRICE_PRO=pri_xxx
PADDLE_PRICE_ENTERPRISE=pri_xxx

composer install
npm install
php artisan key:generate

./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate

npm run dev 
```

Open [http://localhost](http://localhost). 

Mailpit (password-reset emails) at <http://localhost:8025>.

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
| GET | `/billing/pending` | `billing.pending` | Auth required; the post-checkout landing page. Polls every 2s for the `subscription.created` webhook, then redirects to `/billing` |
| GET | `/billing/payment-method` | `billing.payment-method` | Owner only; lazily fetches Paddle's payment-method URL and 302s to it. Redirects back with a session error on Paddle API failure |
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

### Paddle Setup (Step by Step)

#### 1. Create a Paddle account

Go to [paddle.com](https://paddle.com) and sign up. Paddle operates as a Merchant of Record, so they handle global tax
compliance on your behalf — no VAT/GST registrations required.

#### 2. Switch to Sandbox for development

In the Paddle dashboard, use the **Sandbox** toggle in the top-left corner (or navigate to
`sandbox-vendors.paddle.com`). All development and testing should happen in sandbox mode. Set `PADDLE_SANDBOX=true` in
`.env` while working locally.

#### 3. Get your Seller ID

- In the Paddle dashboard go to **Developer Tools > Authentication**.
- Copy the **Seller ID** (a numeric value like `12345`).
- Add it to `.env`:

```env
PADDLE_SELLER_ID=12345
```

#### 4. Create a Product and Prices

Paddle uses a two-level hierarchy: **Products** contain one or more **Prices** (recurring billing intervals /
currencies).

1. Go to **Catalog > Products** and click **New product**.
2. Give it a name (e.g. `ModernSaaS`) and save.
3. With the product open, click **New price** for each plan:
    - **Basic** — e.g. $10/month, `billing_cycle: monthly`
    - **Pro** — e.g. $100/month
    - **Enterprise** — e.g. $500/month
4. After saving each price, copy the **Price ID** (format: `pri_xxxxxxxxxxxxxxxxxxxxxxxx`).
5. Add them to `.env`:

```env
PADDLE_PRICE_BASIC=pri_xxxxxxxxxxxxxxxxxxxxxxxx
PADDLE_PRICE_PRO=pri_xxxxxxxxxxxxxxxxxxxxxxxx
PADDLE_PRICE_ENTERPRISE=pri_xxxxxxxxxxxxxxxxxxxxxxxx
```

These must match the keys in `config/billing.php`.

#### 5. Create an API Key

- Go to **Developer Tools > Authentication > API keys**.
- Click **Generate API key**, give it a name, and copy the key (it's only shown once).
- Add it to `.env`:

```env
PADDLE_API_KEY=your-paddle-api-key
```

#### 6. Create a Client-Side Token

The client-side token is used by `Paddle.js` in the browser to open the checkout overlay. It is safe to expose to the
frontend.

- Go to **Developer Tools > Authentication > Client-side tokens**.
- Click **Generate client-side token**, copy the value.
- Add it to `.env`:

```env
PADDLE_CLIENT_SIDE_TOKEN=your-paddle-client-side-token
```

#### 7. Configure the Webhook

Paddle sends subscription lifecycle events (created, updated, canceled, payment failed, etc.) to your webhook endpoint.
Cashier Paddle verifies the signature automatically.

1. Go to **Developer Tools > Notifications**.
2. Click **New notification**.
3. Set the **URL** to your publicly reachable webhook endpoint:
    - Local dev via tunnel: `https://<your-tunnel>.trycloudflare.com/paddle/webhook`
    - Production: `https://yourdomain.com/paddle/webhook`
4. Under **Events**, select all `subscription.*` and `transaction.*` events (at minimum: `subscription.created`,
   `subscription.updated`, `subscription.canceled`, `transaction.completed`, `transaction.payment_failed`).
5. Save, then click the notification to reveal the **Secret key**.
6. Add it to `.env`:

```env
PADDLE_WEBHOOK_SECRET=your-webhook-secret-key
```

> **Local dev tip**: To receive real webhooks on localhost, expose your app with
`cloudflared tunnel --url http://localhost` and register the tunnel URL. Alternatively, use the built-in
`paddle:fake-webhook` artisan command — no tunnel required (see [Testing webhooks locally](#testing-webhooks-locally)).

#### 8. Complete `.env` configuration

Your final Paddle block in `.env` should look like:

```env
PADDLE_SANDBOX=true
PADDLE_SELLER_ID=12345
PADDLE_API_KEY=your-paddle-api-key
PADDLE_CLIENT_SIDE_TOKEN=your-paddle-client-side-token
PADDLE_WEBHOOK_SECRET=your-webhook-secret-key
PADDLE_PRICE_BASIC=pri_xxxxxxxxxxxxxxxxxxxxxxxx
PADDLE_PRICE_PRO=pri_xxxxxxxxxxxxxxxxxxxxxxxx
PADDLE_PRICE_ENTERPRISE=pri_xxxxxxxxxxxxxxxxxxxxxxxx
```

Set `PADDLE_SANDBOX=false` and swap all sandbox credentials for live credentials when deploying to production.

#### 9. Verify the setup

1. Start the app: `./vendor/bin/sail up -d && npm run dev`
2. Register a user and navigate to `/billing`.
3. Click **Upgrade** on any plan — the Paddle overlay should open.
4. Use a [Paddle sandbox test card](https://developer.paddle.com/concepts/payment-methods/credit-debit-card) to complete
   the checkout.
5. After payment, you should land on `/billing/pending`, then be redirected to `/billing` with the plan showing as
   active once the `subscription.created` webhook lands.

### Checkout flow (Livewire + Paddle.js overlay):

1. User on `/billing` sees `<livewire:billing.plan-picker />`
2. Clicks "Upgrade to Pro" → fires `wire:click="subscribe('pro')"` on `App\Livewire\Billing\PlanPicker`
3. Component authorizes (`manageBilling` policy = Owner only), calls `Domain\Billing\Actions\StartCheckoutAction` which builds a `Laravel\Paddle\Checkout` via `$workspace->subscribe($priceId)` (creating the Paddle customer record on first use; idempotent thereafter)
4. Component dispatches a browser event `paddle-checkout` with the checkout config
5. JS handler in `resources/views/billing/index.blade.php` calls `Paddle.Checkout.open(config)`, the Paddle overlay opens
6. User completes payment in the overlay
7. Paddle sends webhooks to `/paddle/webhook` *and* redirects the user to `/billing/pending?_ptxn=txn_xxx` (the `successUrl` set by `StartCheckoutAction::returnTo()` — a dedicated "Processing payment" page, not back to `/billing` directly)
8. `App\Livewire\Billing\PendingPayment` renders a full-page spinner and `wire:poll`s `checkSubscription()` every 2 seconds. If the workspace already has a subscription on mount (e.g., user refreshed after webhook landed), it redirects straight to `/billing`. After 60 seconds (`PendingPayment::PROCESSING_TIMEOUT_SECONDS`) the polling stops and the page switches to a "Still confirming" state with a manual Check again button and a Back to billing link
9. Cashier creates `subscriptions` + `subscription_items` rows, dispatches `Laravel\Paddle\Events\SubscriptionCreated`
10. `Domain\Billing\Listeners\SyncSubscriptionPlan` writes `workspaces.plan` from the subscription's price ID
11. The next `wire:poll` tick on `PendingPayment` sees the subscription, returns `redirect()->route('billing')`, and the user lands on `/billing` with the normal "Active" view — no manual refresh needed

`SubscriptionPanel` itself is now stateless: it only cares about whether a subscription row exists *now*. All post-checkout polling lives on `PendingPayment`.

The Paddle.js script is injected by `@paddleJS` (a Cashier directive) on the `/billing` page only — we don't load it globally.

### Workspace plan logic

**Reading the current plan**: always call `$workspace->currentPlan()`, never the raw `plan` column.

This will return the current plan for the workspace, taking into account the subscription status and price IDs. It ensures that the plan is always up-to-date and accurate.

You can configure additional plans in `config/billing.php`. The `price_id` column is configured in the Paddle.com Product Catalogue dashboard.

```php
# config/billing.php

return [
    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Each plan key MUST match a case of Domain\Workspaces\Enums\WorkspacePlan.
    | Free has no Paddle price (no subscription is created — the workspace
    | simply has plan='free'). Premium and Enterprise reference Paddle price
    | IDs from the Paddle dashboard (sandbox or live, controlled by
    | PADDLE_SANDBOX).
    |
    */

    'plans' => [
    
        'basic' => [
            'name' => 'Basic',
            'price_id' => env('PADDLE_PRICE_BASIC'),
            'amount' => 1000,
            'features' => [
                '10 conversions / month',
                'PDF, Word doc, slide deck, spreadsheets',
                'Fair rate limits',
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'price_id' => env('PADDLE_PRICE_PRO'),
            'amount' => 10000,
            'features' => [
                'Everything in Free',
                '100 conversions / month',
                'Audio transcription * Coming Soon',
                'Video transcription * Coming Soon',
                'Priority email support',
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'price_id' => env('PADDLE_PRICE_ENTERPRISE'),
            'amount' => 50000,
            'features' => [
                'Everything in Premium',
                '500 conversions / month',
                'SSO + audit logs',
                'Dedicated support',
            ],
        ],
    ],
];
```

The `workspaces.plan` column is a denormalized cache, useful for `WHERE plan = 'pro'` queries.

**Don't authorize off it** — Cashier's `subscribed()` is the source of truth, and `currentPlan()` wraps it.

**Cancellation**: `SubscriptionPanel::cancel()` calls `$subscription->cancel()`. Paddle keeps the subscription active until `ends_at`. The listener does NOT subscribe to `SubscriptionCanceled`, so `workspaces.plan` stays on the paid tier for the read-side cache. Once the grace period elapses, Cashier's `subscribed()` returns false → `currentPlan()` returns Free. No scheduled job needed.

**Resume**: during grace period, `SubscriptionPanel::resume()` calls `$subscription->stopCancelation()` (NOT `resume()` — that method is for *paused* subscriptions and throws `LogicException` on canceled ones).

**Update payment method**: the panel renders a plain link to `route('billing.payment-method')` (handled by `App\Http\Controllers\Billing\PaymentMethodController`). That controller calls `$subscription->paymentMethodUpdateUrl()` *only when the user clicks the button* and 302s straight to Paddle. Two reasons not to call this on render:

- `paymentMethodUpdateUrl()` hits Paddle's API (`GET subscriptions/{id}`) — doing it on every billing-page render adds ~100–300ms per page load.
- If the local `subscriptions` row points at a Paddle ID that no longer exists (stale `paddle:fake-webhook` seed, environment rotation), the render would explode. The deferred-fetch model isolates that failure to the click and redirects back to `/billing` with a session error.


### Testing webhooks locally

Two options:

**1. Fake them with the artisan command** (no internet round-trip):

```bash
# Default: subscription.created against the first workspace that has a Paddle customer
./vendor/bin/sail artisan paddle:fake-webhook

# Pin the event, workspace, plan, and subscription ID
./vendor/bin/sail artisan paddle:fake-webhook subscription.created \
  --workspace=1 --price=enterprise --id=sub_my_test_001

# Other supported events
./vendor/bin/sail artisan paddle:fake-webhook subscription.updated --workspace=1
./vendor/bin/sail artisan paddle:fake-webhook subscription.canceled --workspace=1
./vendor/bin/sail artisan paddle:fake-webhook transaction.completed --workspace=1
```

The command builds a realistic Paddle payload, signs it with `PADDLE_WEBHOOK_SECRET`, and POSTs to your local `/paddle/webhook`. It refuses to run when `APP_ENV=production`. The customer must already exist locally — visit `/billing` as the workspace owner once to create the Paddle customer record (this is the only step that requires Paddle's live API).

**2. Receive real Paddle webhooks** (full round-trip):

Expose the app to the public internet (e.g. `cloudflared tunnel --url http://localhost`) and register the tunnel URL + `/paddle/webhook` in Paddle's dashboard. Paddle will then post real events as customers move through checkout.

## Authorization

`Domain\Workspaces\Policies\WorkspacePolicy` is auto-discovered via `#[UsePolicy]` on `Workspace`:

```php
$user->can('view', $workspace)            // member?
$user->can('manageBilling', $workspace)   // owner only
$user->can('manageMembers', $workspace)   // owner or admin
```

Roles are stored on the `workspace_user` pivot's `role` column as `WorkspaceRole` enum values.

## Plan Middleware

Route middleware: `plan:<csv>` (alias for `App\Http\Middleware\RequiresPlan`).

```php
Route::middleware(['auth', 'plan:pro,enterprise'])->group(function () {
    // routes only available to Pro and Enterprise workspaces
});

Route::middleware(['auth', 'plan:enterprise'])->group(function () {
    // enterprise-only
});
```

The middleware reads `$user->currentWorkspace->currentPlan()`, so grace-period downgrades happen automatically.

## Quality of life dev defaults

`AppServiceProvider::boot()` wires:
- `Model::shouldBeStrict()` in non-production — lazy loading, missing attributes, and silently-discarded attributes all throw. Fix the call site rather than relax the rule.
- `DB::prohibitDestructiveCommands()` in production — blocks `migrate:fresh`, `db:wipe`, etc.
- `Factory::guessFactoryNamesUsing()` — keeps factory resolution working as we add more models.

## Testing

```bash
./vendor/bin/sail artisan test
```

Feature tests covering:
 - registration + workspace bootstrap
 - login
 - password reset
 - dashboard auth gate
 - billing page render
 - plan middleware + workspace policy
 - `SyncSubscriptionPlan` listener (Pro/Enterprise/Updated/unknown-price fallback)
 - `PlanPicker` Livewire checkout dispatch (via `Cashier::fake()`)
 - `SubscriptionPanel` cancel/resume/error paths (via `Cashier::fake()`)
 - `PendingPayment` post-checkout landing page (auth gate, render with `_ptxn`, redirect to `/billing` once the subscription appears, timeout state)
 - `PaymentMethodController` (owner-only authz, redirect to Paddle, graceful fallback on API error / missing subscription)
 - Paddle webhook signature verification (valid, missing, tampered, stale-timestamp)
 - `paddle:fake-webhook` artisan command (payload shape + HMAC signature header)

Tests use `LazilyRefreshDatabase` against the Sail Postgres container — each test runs in a transaction so data doesn't leak.

## Console Commands

```bash
./vendor/bin/sail up -d                                          # start containers
./vendor/bin/sail artisan migrate                                # apply migrations
./vendor/bin/sail artisan test                                   # run the suite
./vendor/bin/sail artisan tinker                                 # REPL
./vendor/bin/sail artisan paddle:fake-webhook [event] [options]  # sign + POST a Paddle webhook locally (dev only)
./vendor/bin/pint                                                # format
./vendor/bin/phpstan analyse                                     # static analysis (Larastan, level 6)

npm run dev                                                      # Vite dev server (host-side)
npm run build                                                    # production bundle
```

## References

- [Laravel Cashier](https://laravel.com/docs/billing)
- [Laravel Cashier Paddle](https://github.com/laravel/cashier-paddle)
- [Paddle Docs](https://developer.paddle.com/docs)
- [Paddle Webhooks](https://developer.paddle.com/webhooks)
