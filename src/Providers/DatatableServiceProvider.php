<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

/**
 * DatatableServiceProvider
 * 
 * - Checks if Yajra Datatables package is installed.
 * - Installs it automatically via Composer if not present.
 * - Publishes the package assets.
 */
class DatatableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Check if Yajra Datatables class exists
        if (!class_exists(\Yajra\DataTables\DataTables::class)) {
            $this->installYajra();
        }
    }

    /**
     * Install Yajra Datatables via Composer and publish assets.
     */
    protected function installYajra(): void
    {
        echo "Yajra Datatables is not installed, installing via Composer...\n";

        // Install the package
        passthru('composer require yajra/laravel-datatables:"^12"');

        // Publish the package assets
        Artisan::call('vendor:publish', [
            '--provider' => 'Yajra\DataTables\DataTablesServiceProvider'
        ]);

        echo "Yajra Datatables has been successfully installed!\n";
    }
}
