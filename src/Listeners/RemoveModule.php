<?php

namespace IbnulHusainan\Arc\Listeners;

use IbnulHusainan\Arc\Events\TableRolledBack;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Listener to automatically remove module skeleton
 * whenever a migration rollback for that module's table is executed.
 */
class RemoveModule
{
    /**
     * Handle the event.
     *
     * @param \IbnulHusainan\Arc\Events\TableRolledBack $event
     * @return void
     */
    public function handle(TableRolledBack $event): void
    {
        Artisan::call('remove:module', [
            'modules' => [$event->module]
        ]);

        Log::info("Module [{$event->module}] removed after rollback.");
    }
}
