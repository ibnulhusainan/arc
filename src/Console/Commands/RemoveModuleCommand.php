<?php

namespace IbnulHusainan\Arc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:module
                            {modules* : The name(s) of the module(s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove module(s) including all related folders and files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modules = $this->argument('modules');
        
        foreach ($modules as $module) {
            $this->removeModule($module);
            cmdRemoved($module);
        }
            

        return self::SUCCESS;
    }

    /**
     * Remove a module directory.
     *
     * @param string $module
     * @return void
     */
    protected function removeModule(string $module): void
    {
        $module = implode(DIRECTORY_SEPARATOR, array_map('ucfirst', explode(DIRECTORY_SEPARATOR, $module)));
        $path = dirname(arcModulesPath($module));

        if (File::exists($path)) {
            File::deleteDirectory($path);
            $this->info("Module [{$module}] has been removed.");
        } else {
            $this->warn("Module [{$module}] does not exist.");
        }
    }
}
