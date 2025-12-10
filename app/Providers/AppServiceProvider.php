<?php

namespace App\Providers;

use App\Repositories\Division\DivisionRepository;
use App\Repositories\Division\EloquentDivisionRepository;
use App\Repositories\Position\EloquentPositionRepository;
use App\Repositories\Position\PositionRepository;
use App\Repositories\Role\EloquentRoleRepository;
use App\Repositories\Role\RoleRepository;
use App\Repositories\User\EloquentUserRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(DivisionRepository::class, EloquentDivisionRepository::class);
        $this->app->singleton(PositionRepository::class, EloquentPositionRepository::class);
        $this->app->singleton(UserRepository::class, EloquentUserRepository::class);
        $this->app->singleton(RoleRepository::class, EloquentRoleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
