<?php
namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap module views.
     *
     * - Scan all modules under app/Modules.
     * - Automatically load views from Templates/Views and Templates/Emails.
     * - Register view namespaces based on module key.
     */
    public function boot(): void
    {
        $modulesPath = arcModulesPath();
        
        $modules = arcModules();

        foreach ($modules as $modulePath) {
            $module = str_replace($modulesPath , '', $modulePath);
            $moduleKey = strtolower(str_replace(DIRECTORY_SEPARATOR, '.', $module));

            $viewPath = $modulePath . '/Templates/Views';
            if (is_dir($viewPath) && !empty(File::allFiles($viewPath))) {
                $this->loadViewsFrom($viewPath, $moduleKey);
            }

            $componentPath = $modulePath . '/Templates/Views/Components';
            if (is_dir($componentPath) && !empty(File::allFiles($componentPath))) {
                Blade::anonymousComponentPath($componentPath, $moduleKey);
            }

            $emailPath = $modulePath . '/Templates/Emails';
            if (is_dir($emailPath) && !empty(File::allFiles($emailPath))) {
                $this->loadViewsFrom($emailPath, $moduleKey . 'mail');
            }


            $componentPath = $modulePath . '/Templates/Emails/Components';
            if (is_dir($componentPath) && !empty(File::allFiles($componentPath))) {
                Blade::anonymousComponentPath($componentPath, $moduleKey . 'mail');
            }
        }
    }

    /**
     * Register artisan commands for module scaffolding.
     */
    public function register(): void
    {
        $this->commands([
            \IbnulHusainan\Arc\Console\Commands\MakeModuleCommand::class,
            \IbnulHusainan\Arc\Console\Commands\RemoveModuleCommand::class
        ]);
    }
}
