<?php
namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\ServiceProvider;
use IbnulHusainan\Arc\Supports\RouteMacro;
use Illuminate\Support\Facades\File;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap module route loading.
     *
     * - Initialize custom route macros.
     * - Scan all modules for "Routes" folders.
     * - Automatically load all PHP files inside subfolders containing "Route".
     */
    public function boot(): void
    {
        RouteMacro::init();

        $modules = array_filter(arcModules(), fn($module) => str_ends_with($module, 'Routes'));

        foreach ($modules as $modulePath) {
            foreach (File::allFiles($modulePath) as $file) {
                if (str_ends_with($file->getFileName(), 'Route.php')) {
                    $this->loadRoutesFrom($file->getRealPath());
                }
            }
        }
    }
}
