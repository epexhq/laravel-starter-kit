<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureHealthCheck();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }

    /**
     * Make the `/up` health route fail when the database, the cache store or the session store
     * is unreachable, so readiness checks stop sending traffic to an app that can't serve pages.
     */
    protected function configureHealthCheck(): void
    {
        Event::listen(function (DiagnosingHealth $event): void {
            DB::select('select 1');
            Cache::get('health-check');
            Session::getHandler()->read('health-check');
        });
    }
}
