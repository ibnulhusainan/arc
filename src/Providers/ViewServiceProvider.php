<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap all module-related view composers.
     * 
     * This will automatically attach dynamic columns and routes
     * to views that belong to modules.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (!str_contains($view->getName(), '::') || str_contains($view->getName(), 'exception')) {
                return;
            }

            [$viewModulePath, $viewModuleName] = explode('::', $view->getName());

            $this->registerColumns($view, $viewModulePath, $viewModuleName);
        });
    }

    /**
     * Register dynamic database columns for a given module view.
     * 
     * - Checks for view-specific columns (e.g., $formColumns)
     * - Falls back to global $viewsColumns or schema introspection
     * - Supports exclusion rules via *ColumnsExcept properties
     */
    private function registerColumns($view, string $viewModulePath, string $viewModuleName): void
    {
        $moduleParts    = explode('.', $viewModulePath);
        $modelClassName = ucfirst(end($moduleParts));
        $modelClass     = arcModuleNamespace() . '\\' . implode('\\', array_map('ucfirst', $moduleParts)) . '\\Models\\' . $modelClassName;
        $columns        = [];
        $pk             = 'id';

        if (class_exists($modelClass)) {
            $model                 = new $modelClass;
            $dynamicProperty       = $viewModuleName . 'Columns';
            $dynamicExceptProperty = $viewModuleName . 'ColumnsExcept';

            $columns = !empty($model->{$dynamicProperty})
                ? $model->{$dynamicProperty}
                : (!empty($model->viewsColumns)

                    ? $model->viewsColumns
                    : (!empty($model->getFillable())

                        ? $model->getFillable()
                        : []
                    ));

            $exceptColumns = !empty($model->{$dynamicExceptProperty})
                ? $model->{$dynamicExceptProperty}
                : ($model->viewsColumnsExcept ?? []);

            $columns = collect(arcViewsColumns($columns))
                ->except($exceptColumns)
                ->toArray();

            $pk = $model->getKeyName();            
        }
            
        $view->with('pk', $pk);
        $view->with('columns', $columns);
    }
}
