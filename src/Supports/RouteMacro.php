<?php

namespace IbnulHusainan\Arc\Supports;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\RouteRegistrar;

class RouteMacro
{
    public static $deferredRoutes = [];

    public static function init()
    {
        static::crud();
    }

    private static function crud()
    {
        Route::macro('crud', function (string $uri, string $controller, string $pk = 'id') {
            $namespace = last(Route::getGroupStack())['namespace'] ?? '';

            $controllerClass = "{$namespace}\\{$controller}";
            $controllerModel = app($controllerClass)?->getService()?->getRepository()?->getModel();
            $modelClass = $controllerModel ? get_class($controllerModel) : controllerTo('model', $controllerClass);

            $pk = class_exists($modelClass) ? app($modelClass)->getKeyName() : $pk;

            $prefix = trim($uri, '/');
            $name = str_replace('/', '.', $prefix);
            
            $registrar = Route::middleware('web')
                ->controller($controllerClass)
                ->prefix($prefix)
                ->name("{$name}.");
            
            RouteMacro::$deferredRoutes[$name] = [
                'registrar' => $registrar,
                'modelClass' => $modelClass,
                'allRoutes' => [
                    'list' => ['method' => 'get', 'uri' => ''],
                    'data' => ['method' => 'get', 'uri' => 'data'],
                    'form' => ['method' => 'get', 'uri' => "form/{{$pk}?}"],
                    'detail' => ['method' => 'get', 'uri' => "detail/{{$pk}}"],
                    'save' => ['method' => 'post', 'uri' => 'save'],
                    'delete' => ['method' => 'delete', 'uri' => 'delete'],
                ],
                'only' => null,
                'except' => [],
            ];

            $registrar->macro('only', function (string|array $methods) use ($name) {
                RouteMacro::$deferredRoutes[$name]['only'] = (array) $methods;
                RouteMacro::registerDeferredRoutes($name);
                return $this;
            });

            $registrar->macro('except', function (string|array $methods) use ($name) {
                RouteMacro::$deferredRoutes[$name]['except'] = (array) $methods;
                RouteMacro::registerDeferredRoutes($name);
                return $this;
            });

            $registrar->macro('groupAs', function(string $uri, \Closure $callback) use ($name, $controller) {
                $prefix = trim($uri, '/');
                $newName = str_replace('/', '.', $prefix);

                RouteMacro::registerDeferredRoutes($name);

                return (clone $this)
                    ->prefix($prefix)
                    ->name("{$newName}.")
                    ->group($callback);
            });            

            $registrar->macro('withPolicy', function (array $policies = []) use ($name, $modelClass) {
                RouteMacro::registerDeferredRoutes($name);

                $controllerClass = $this->attributes['controller'];
                $lastUri = substr(strrchr(trim(request()->getRequestUri(), '/'), '/'), 1);

                collect(Route::getRoutes()->getRoutes())
                    ->filter(fn($route) => $route->getControllerClass() === $controllerClass)
                    ->each(function($route) use($policies, $modelClass, $lastUri) {
                        $method = $route->getActionMethod();
                        $policy = $policies[$method] ?? match($method) {
                            'list', 'data', 'detail' => ($lastUri === $method) ? 'view' : 'viewAny',
                            'form', 'save' => ($lastUri === $method) ? 'create' : 'update',
                            'delete' => 'delete',
                            default => $method
                        };

                        $route->middleware("policy:{$policy},{$modelClass}");
                    });

                return $this;
            });

            app()->booted(function() use ($name) {
                if (array_key_exists($name, RouteMacro::$deferredRoutes) && !RouteMacro::$deferredRoutes[$name]['only'] && empty(RouteMacro::$deferredRoutes[$name]['except'])) {
                    RouteMacro::registerDeferredRoutes($name);
                }
            });
           
            return $registrar;
        });
    }
    
    public static function registerDeferredRoutes(string $name)
    {
        if (!array_key_exists($name, static::$deferredRoutes)) {
            return;
        }

        $config = static::$deferredRoutes[$name];
        
        $routesToRegister = $config['allRoutes'];

        if ($config['only']) {
            $routesToRegister = array_intersect_key($config['allRoutes'], array_flip($config['only']));
        }
        
        if ($config['except']) {
            $routesToRegister = array_diff_key($routesToRegister, array_flip($config['except']));
        }
        
        $config['registrar']->group(function() use($routesToRegister){
            foreach ($routesToRegister as $routeName => $routeConfig) {
                Route::{$routeConfig['method']}($routeConfig['uri'], $routeName)->name($routeName);
            }
        });

        unset(static::$deferredRoutes[$name]);
    }
}
