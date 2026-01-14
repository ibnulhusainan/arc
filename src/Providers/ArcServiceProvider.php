<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

/**
 * Class ArcServiceProvider
 *
 * Main service provider for Arc package.
 *
 * Responsibilities:
 *  - Register all sub-providers (Modules, Routes, Views, Datatables, Events).
 *  - Ensure `app/Modules` directory exists on boot.
 *  - Publish Arc configuration, assets, and views to host application.
 *
 * Notes:
 *  - Only this provider needs to be registered in composer.json.
 *  - Sub-providers are registered internally inside `register()` method.
 *  - Publishing is grouped into specific tags for selective publishing:
 *      --tag=arc-config
 *      --tag=arc-assets-css
 *      --tag=arc-assets-js
 *      --tag=arc-views
 *
 * @package IbnulHusainan\Arc\Providers
 */
class ArcServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->createModulesDir();
        $this->publish();
    }

    /**
     * Register package sub-providers.
     */
    public function register(): void
    {
        foreach ([
            \IbnulHusainan\Arc\Providers\HelperServiceProvider::class,
            \IbnulHusainan\Arc\Providers\ModuleServiceProvider::class,
            \IbnulHusainan\Arc\Providers\ViewServiceProvider::class,
            \IbnulHusainan\Arc\Providers\PolicyServiceProvider::class,
            \IbnulHusainan\Arc\Providers\DatatableServiceProvider::class,
            \IbnulHusainan\Arc\Providers\EventServiceProvider::class,
            \IbnulHusainan\Arc\Providers\BladeDirectiveServiceProvider::class,
            \IbnulHusainan\Arc\Providers\MiddlewareServiceProvider::class,
            \IbnulHusainan\Arc\Providers\RouteServiceProvider::class,
        ] as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Define publishable resources with specific tags.
     */
    private function publish(): void
    {
        $this->publishes([
            __DIR__ . '/../config/arc.php' => config_path('arc.php'),
        ], 'arc-config');

        $this->publishes([
            __DIR__ . '/../resources/css' => resource_path('css'),
        ], 'arc-assets-css');

        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js'),
        ], 'arc-assets-js');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views'),
        ], 'arc-views');

        $cssPath = resource_path('css/app.css');
        if(File::exists($cssPath)) {
            $appCss = File::get($cssPath);
            if(!str_contains($appCss, './datatable.css')) {
                $newCss = str_replace("@import 'tailwindcss';", "@import 'tailwindcss';". PHP_EOL ."@import './datatable.css';", $appCss);
                File::put($cssPath, $newCss);
            }
        }

        $jsPath = resource_path('js/app.js');
        if(File::exists($jsPath)) {
            $appJs = File::get($jsPath);
            if(!str_contains($appJs, './arc.js')) {
                $newJs = $appJs . PHP_EOL . "import './arc.js';";
                File::put($jsPath, $newJs);
            }
        }
    }

    /**
     * Ensure `app/Modules` directory exists, otherwise create it.
     *
     * @throws \RuntimeException if directory creation fails.
     */
    private function createModulesDir(): void
    {
        $modulesPath = app_path('Modules/');

        if (!is_dir($modulesPath) && !mkdir($modulesPath, 0755, true) && !is_dir($modulesPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $modulesPath));
        }
    }
}
 