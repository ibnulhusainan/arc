<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * PolicyServiceProvider
 *
 * Automatically discovers and registers policy classes
 * from ARC modules based on naming and directory conventions.
 *
 * Policy files must:
 * - Be located inside a `Policies` directory
 * - End with `Policy.php`
 * - Have a corresponding model resolved via `policyTo()`
 *
 * This enables zero-configuration policy registration
 * for modular ARC-based applications.
 */
class PolicyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap policy discovery and registration.
     *
     * @return void
     */
    public function boot(): void
    {
        $modules = array_filter(
            arcModules(),
            fn ($module) => str_ends_with($module, 'Policies')
        );

        foreach ($modules as $modulePath) {

            foreach (File::allFiles($modulePath) as $file) {
                $fileName = $file->getFilename();

                // Only process policy classes
                if (!str_ends_with($fileName, 'Policy.php')) {
                    continue;
                }

                $policyName = $file->getFilenameWithoutExtension();

                // Resolve policy namespace from file path
                $policyNamespace = trim(
                    str_replace(
                        [base_path(), DIRECTORY_SEPARATOR],
                        ['', '\\'],
                        $file->getPath()
                    ),
                    '\\'
                );

                $policyClass = ucfirst("{$policyNamespace}\\{$policyName}");
                $modelClass  = policyTo('model', $policyClass);

                // Register policy if both policy and model exist
                if (class_exists($policyClass) && class_exists($modelClass)) {
                    Gate::policy($modelClass, $policyClass);
                }
            }
        }
    }
}
