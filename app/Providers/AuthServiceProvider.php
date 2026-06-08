<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\SupportRequest;
use App\Models\Message;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Product;
use App\Models\VendorProfile;
use App\Policies\DisputePolicy;
use App\Policies\SupportRequestPolicy;
use App\Policies\MessagePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\VendorPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SupportRequest::class => SupportRequestPolicy::class,
        Message::class => MessagePolicy::class,
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        Dispute::class => DisputePolicy::class,
        VendorProfile::class => VendorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
