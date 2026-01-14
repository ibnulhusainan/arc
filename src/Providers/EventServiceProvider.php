<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use IbnulHusainan\Arc\Events\TableMigrated;
use IbnulHusainan\Arc\Events\TableRolledBack;
use IbnulHusainan\Arc\Listeners\GenerateModule;
use IbnulHusainan\Arc\Listeners\RemoveModule;

/**
 * EventServiceProvider
 *
 * Registers ARC package event-to-listener mappings.
 *
 * This provider listens to database-related events and
 * triggers automatic module generation or cleanup
 * based on migration lifecycle events.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the ARC package.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        /**
         * Fired after a table has been successfully migrated.
         * Triggers automatic module generation.
         */
        TableMigrated::class => [
            GenerateModule::class,
        ],

        /**
         * Fired after a table migration has been rolled back.
         * Triggers automatic module removal.
         */
        TableRolledBack::class => [
            RemoveModule::class,
        ],
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }
}
