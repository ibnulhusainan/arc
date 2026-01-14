<?php

namespace IbnulHusainan\Arc\Listeners;

use IbnulHusainan\Arc\Events\TableMigrated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Listener to automatically generate a module skeleton
 * whenever a migration for that module's table is executed.
 */
class GenerateModule
{
    /**
     * Handle the event.
     *
     * @param \IbnulHusainan\Arc\Events\TableMigrated $event
     * @return void
     */
    public function handle(TableMigrated $event): void
    {
        $opts = Cache::get("arc:opts:{$event->table}", ['only' => null, 'except' => null]);

        Artisan::call('make:module', [
            'modules' => [$event->module],
            '--table' => $event->table,
            '--only'  => $opts['only'],
            '--except'  => $opts['except']
        ]);

        Log::info("Module [{$event->module}] generated from table [{$event->table}] after migration.");
    }
}
