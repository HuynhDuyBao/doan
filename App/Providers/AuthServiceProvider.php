<?php

namespace App\Providers;

use App\Models\Movie;
use App\Policies\MoviePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Movie::class => MoviePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
