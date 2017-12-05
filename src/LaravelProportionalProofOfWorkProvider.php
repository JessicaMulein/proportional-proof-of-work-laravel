<?php

namespace JessicaMulein\LaravelProportionalProofOfWork;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class LaravelProportionalProofOfWorkProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('jessie-jensen.ppow', LaravelProportionalProofOfWorkMiddleware::class);
    }
}
