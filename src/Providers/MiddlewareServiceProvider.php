<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * MiddlewareServiceProvider
 *
 * Registers custom middleware aliases used by the ARC package.
 *
 * This allows ARC-specific middleware to be referenced by
 * short, expressive names within route definitions.
 */
class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register middleware aliases.
     *
     * @param Router $router
     * @return void
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware(
            'fakeauth',
            \IbnulHusainan\Arc\Middleware\FakeAuth::class
        );

        $router->aliasMiddleware(
            'policy',
            \IbnulHusainan\Arc\Middleware\Policy::class
        );
    }
}
