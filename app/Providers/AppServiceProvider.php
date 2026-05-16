<?php

namespace App\Providers;

use Domain\Billing\Listeners\SyncSubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionUpdated;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Event::listen(SubscriptionCreated::class, [SyncSubscriptionPlan::class, 'handleSubscriptionCreated']);
        Event::listen(SubscriptionUpdated::class, [SyncSubscriptionPlan::class, 'handleSubscriptionUpdated']);
    }
}
