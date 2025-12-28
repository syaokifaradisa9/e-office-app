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
use Modules\VisitorManagement\Repositories\FeedbackQuestion\EloquentFeedbackQuestionRepository;
use Modules\VisitorManagement\Repositories\FeedbackQuestion\FeedbackQuestionRepository;
use Modules\VisitorManagement\Repositories\Purpose\EloquentPurposeRepository;
use Modules\VisitorManagement\Repositories\Purpose\PurposeRepository;
use Modules\VisitorManagement\Repositories\Visitor\EloquentVisitorRepository;
use Modules\VisitorManagement\Repositories\Visitor\VisitorRepository;

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

        // Visitor Management
        $this->app->singleton(PurposeRepository::class, EloquentPurposeRepository::class);
        $this->app->singleton(FeedbackQuestionRepository::class, EloquentFeedbackQuestionRepository::class);
        $this->app->singleton(VisitorRepository::class, EloquentVisitorRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
