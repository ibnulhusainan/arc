<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * HelperServiceProvider
 *
 * Automatically loads helper files from all ARC modules.
 *
 * This provider scans each registered module for a `Helpers`
 * directory and includes all PHP files found within it.
 *
 * Intended for globally available helper functions that
 * should be accessible throughout the application lifecycle.
 */
class HelperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap helper loading for ARC modules.
     *
     * @return void
     */
    public function boot(): void
    {
        $modules = array_filter(
            arcModules(),
            fn ($module) => str_ends_with($module, 'Helpers')
        );

        foreach ($modules as $modulePath) {
            foreach (File::allFiles($modulePath) as $file) {
                require_once $file->getPathname();
            }
        }
    }
}
