<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

if(!function_exists('arcModulesPath')) {
    function arcModulesPath(?string $module = null)
    {
        return '/' . trim(config('arc.modules_path') ?? app_path('Modules'), '/') . '/' . $module;
    }
}

if(!function_exists('arcModuleNamespace')) {
    function arcModuleNamespace()
    {
        return ucfirst(
            str_replace(
                [base_path() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                ['', '\\'],
                rtrim(arcModulesPath(), '/')
            )
        );
    }
}

if(!function_exists('arcModules')) {
    function arcModules(): array
    {
        $modulePath = config('arc.modules_path') ?? app_path('Modules');

        if(!file_exists($modulePath)) {
            mkdir($modulePath, 0775, true);
        }

        $modules = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                $modules[] = $fileInfo->getRealPath();
            }
        }

        return $modules;        
    }
}

if (!function_exists('arcMenus')) {
    function arcMenus(?string $path = null, ?string $prefix = null): array
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn($route) => str_contains($route->getName(), '.list'));

        $menuItems = [];

        foreach ($routes as $route) {
            $prefixPath = trim($route->getPrefix(), '/');
            if (!$prefixPath) continue;

            $segments = array_filter(explode('/', $prefixPath));
            $routeName = $route->getName();

            $nested = array_reduce(
                array_reverse($segments),
                function ($carry, $segment) use ($segments, $routeName) {
                    $isLast = $segment === last($segments);
                    return [
                        'title' => Str::title(str_replace('_', ' ', $segment)),
                        'route' => $isLast ? route($routeName) : null,
                        'children' => $carry ? [$carry] : [],
                    ];
                },
                []
            );

            $menuItems[] = $nested;
        }

        return $menuItems;
    }
}

if (!function_exists('arcViewsColumns')) {
    function arcViewsColumns(array $columns): array
    {
        $nColumns = [];

        foreach ($columns as $key => $value) {
            if (is_array($value)) {
                $nColumns[$key] = arcViewsColumns($value);
            } else {
                $nColumns[is_int($key) ? $value : $key] = Str::title(str_replace('_', ' ', $value));
            }
        }

        return $nColumns;
    }
}

if(!function_exists('arcModuleRoutes')) {
    function arcModuleRoutes(?string $modulePath = null)
    {
        $modulePath = str_replace('/', '.', $modulePath ?? trim(request()->route()?->getPrefix(), '/'));

        $allRoutes = Route::getRoutes();

        $moduleRoutes = collect($allRoutes)->filter(function ($route) use ($modulePath) {
            return str_starts_with($route->getName() ?? '', $modulePath . '.');
        });

        $requestParams = request()->route()?->parameters();

        $routes = $moduleRoutes->mapWithKeys(function ($route) use ($modulePath, $requestParams) {
            $routeParams = $route->parameterNames();
            $params = collect($routeParams)
                ->flip()
                ->map(fn($item, $key) => $requestParams[$key] ?? "_{$key}_")
                ->filter(fn($item) => filled($item))
                ->toArray();

            $shortName = str_replace($modulePath . '.', '', $route->getName());
            return [$shortName => route($route->getName(), $params)];
        });

        return $routes;
    }
}

if (!function_exists('arcScripts')) {
    function arcScripts(string|array|null $scriptNames = null): array
    {
        $libraries    = config('arc.libraries', []);
        $dependencies = config('arc.dependencies', []);

        $scriptNames = $scriptNames === null
            ? array_keys($libraries)
            : array_filter((array) $scriptNames);

        $assets = [
            'css' => [],
            'js'  => [],
        ];

        $pushAsset = function ($asset) use (&$assets) {
            foreach ((array) $asset as $url) {
                if (!$url) continue;

                if (str_ends_with($url, '.css')) {
                    $assets['css'][] = $url;
                } else {
                    $assets['js'][] = $url;
                }
            }
        };

        foreach ($scriptNames as $rawName) {

            // parse plugins
            if (str_contains($rawName, '[')) {
                [$name, $pluginPart] = explode('[', $rawName, 2);
                $plugins = explode(',', rtrim($pluginPart, ']'));
            } else {
                $name = $rawName;
                $plugins = [];
            }

            $name = strtolower(trim($name));
            if (!isset($libraries[$name])) {
                continue;
            }

            $lib = $libraries[$name];

            // dependencies
            foreach ($lib['requires'] ?? [] as $depKey) {
                if (isset($dependencies[$depKey])) {
                    $pushAsset($dependencies[$depKey]);
                }
            }

            // base assets
            foreach ($lib['assets'] ?? [] as $type => $asset) {
                if ($type === 'plugins') continue;
                $pushAsset($asset);
            }

            // plugins
            foreach ($plugins as $plugin) {
                $plugin = strtolower(trim($plugin));
                if (isset($lib['assets']['plugins'][$plugin])) {
                    $pushAsset($lib['assets']['plugins'][$plugin]);
                }
            }
        }

        return [
            array_values(array_unique($assets['css'])),
            array_values(array_unique($assets['js'])),
        ];
    }
}