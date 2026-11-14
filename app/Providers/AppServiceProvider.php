<?php

namespace App\Providers;

use App\Contracts\CatalogRepositoryInterface;
use App\Repositories\EloquentCatalogRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CatalogRepositoryInterface::class, EloquentCatalogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
