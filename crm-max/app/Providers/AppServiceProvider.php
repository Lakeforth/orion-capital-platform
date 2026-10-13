<?php

namespace App\Providers;

use App\Mail\VerifyAccount;
use App\Models\Trade;
use App\Observers\TradeObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use ProtoneMedia\Splade\Facades\Splade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One reader per request or job: the database file is replaced monthly.
        $this->app->scoped(\App\Services\IpCountry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Trade::observe(TradeObserver::class);

        // Behind a load balancer or CDN, request()->ip() is the proxy's address unless the
        // proxy is trusted; activity and login history need the client's.
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
        \App\Models\User::roleAdded(fn ($user, $role) => app(\App\Services\LeadMax\LeadTradingAccounts::class)->onRoleAdded($user, $role));

        //        if(app()->isProduction()) {
        //            URL::forceScheme('https');
        //        }

        LogViewer::auth(function ($request) {
            return true;
        });

        Splade::defaultToast(function ($toast) {
            $toast->info()->rightTop()->autoDismiss(10);
        });

        //        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
        ////            return Mail::to($notifiable->email)->send(new VerifyAccount($url));
        //
        //            return (new MailMessage)
        //                ->subject('Verify Email Address')
        //                ->line('Click the button below to verify your email address.')
        //                ->action('Verify Email Address', $url);
        //        });
    }
}
