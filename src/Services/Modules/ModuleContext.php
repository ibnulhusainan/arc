<?php

namespace IbnulHusainan\Arc\Services\Modules;
use Illuminate\Support\Str;

class ModuleContext
{
    public string $modulePath;
    public string $moduleBasePath;
    public string $moduleName;
    public string $moduleNameSpace;
    public string $tableName;
    public array  $tableColumns;
    public string $routePrefix;

    public static function make(string $module, ?array $opts = null)
    {
        $module = Str::studly(strtolower($module));
        $_module = array_map('ucfirst', explode("/", $module));

        $instance = new self();
        $instance->modulePath = implode("/", $_module);
        $instance->moduleBasePath = arcModulesPath($instance->modulePath);
        $instance->moduleName = last($_module);
        $instance->moduleNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $instance->modulePath);
        
        $instance->moduleOnly = array_filter(array_map('ucfirst', explode(',', $opts['only'])) ?? []);
        $instance->moduleExcept = array_filter(array_map('ucfirst', explode(',', $opts['except'])) ?? []);

        $instance->tableName = $opts['table'] ?? Str::plural(strtolower($instance->moduleName));
        $instance->routePrefix = strtolower($instance->modulePath);

        return $instance;
    }
}