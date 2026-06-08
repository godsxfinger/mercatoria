<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\Payments\MoneroPaymentGateway;
use App\Services\Payments\PaymentGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MoneroPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('auth-forms', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('auth-2fa', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });

        RateLimiter::for('messages', function (Request $request) {
            $key = $request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(20)->by($key);
        });

        RateLimiter::for('support', function (Request $request) {
            $key = $request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(12)->by($key);
        });

        RateLimiter::for('disputes', function (Request $request) {
            $key = $request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('notifications', function (Request $request) {
            $key = $request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(30)->by($key);
        });

        RateLimiter::for('admin-actions', function (Request $request) {
            $key = $request->user() ? 'admin:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(30)->by($key);
        });

        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');

        // Set Carbon locale to English
        Carbon::setLocale('en');
    }
}
