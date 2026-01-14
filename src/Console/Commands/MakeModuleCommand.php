<?php

namespace IbnulHusainan\Arc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use IbnulHusainan\Arc\Services\TableSchema;
use IbnulHusainan\Arc\Services\Modules\ModuleContext;
use IbnulHusainan\Arc\Services\Modules\ModuleGenerator;
use IbnulHusainan\Arc\Supports\ConsoleOutput;

class MakeModuleCommand extends Command
{
    use ConsoleOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module
                            {modules*}
                            {--only= : Generate only the specified module (comma-separated). e.g., controller,model,repository }
                            {--except= : Exclude the specified components (comma-separated). e.g.,: policy,datatable,request }
                            {--table= : Custom database table name}
                            {--skip-table : Skip table check (code-first)}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a full-feature module skeleton';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modules   = $this->argument('modules');
        $table     = $this->option('table');
        $skipTable = $this->option('skip-table');
        $options   = $this->options(); 

        if(count($modules) > 1 && $table) {
            $this->lineError("The --table option can be used only with a single module.");
            return;
        }

        foreach($modules as $module) {
            $moduleContext = ModuleContext::make($module, $options);
            $moduleGenerator = new ModuleGenerator($moduleContext, $options);

            if(!$skipTable) {
                $tableSchema = new TableSchema($moduleContext->tableName);

                if(!$tableSchema->exists()) {                 
                    $this->lineError("Table *[{$moduleContext->tableName}]* not found.");

                    Cache::forever("arc:opts:{$moduleContext->tableName}", $options);

                    $moduleGenerator->makeMigration();
                    continue;
                }

                $moduleContext->schema = (object) $tableSchema->schemas();
            }

            $moduleGenerator->makeModules();
        }
    }
}

