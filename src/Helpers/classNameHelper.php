<?php

use Illuminate\Support\Str;

if(!function_exists('classNameMap')) {
    function classNameMap($from, $target, $className = ''): array
    {
        return [
            'controller' => [
                'model' => ['Controllers' => 'Models', 'Controller' => ''],
                'policy' => ['Controllers' => 'Policies', 'Controller' => 'Policy'],
                'service' => ['Controllers' => 'Services', 'Controller' => 'Service'],
                'saveRequest' => ['Controllers\\' => 'Requests\\Save', 'Controller' => 'Request'],
                'deleteRequest' => ['Controllers\\' => 'Requests\\Delete', 'Controller' => 'Request'],
                'view' => [arcModuleNamespace(). '\\' => '', '\\Controllers\\' . class_basename($className) => '', '\\' => '.'],
            ],
            'repository' => [
                'model' => ['Repositories' => 'Models', 'Repository' => '']
            ],
            'service' => [
                'repository' => ['Services' => 'Repositories', 'Service' => 'Repository'],
                'datatable' => ['Services' => 'Datatables', 'Service' => 'Datatable']
            ],
            'model' => [
                'policy' => ['Models' => 'Policies', 'Repository' => '', 'suffix' => 'Policy']
            ],
            'policy' => [
                'model' => ['Policies' => 'Models', 'Policy' => '']
            ],
        ][$from][$target] ?? [];
    }
}

if(!function_exists('classNameMapper')) {
    function classNameMapper($className, $map): string
    {
        return str_replace(
            array_keys($map),
            array_values($map),
            $className
        ) . ($map['suffix'] ?? '');
    }
}

if(!function_exists('controllerTo')) {
    function controllerTo(string $target, string $className): string
    {
        return classNameMapper($className, classNameMap('controller', $target, $className));   
    }
}

if(!function_exists('repositoryTo')) {
    function repositoryTo(string $target, string $className): string
    {
        return classNameMapper($className, classNameMap('repository', $target, $className));   
    }
}

if(!function_exists('serviceTo')) {
    function serviceTo(string $target, string $className): string
    {
        return classNameMapper($className, classNameMap('service', $target, $className));   
    }
}

if(!function_exists('modelTo')) {
    function modelTo(string $target, string $className): string
    {
        return classNameMapper($className, classNameMap('model', $target, $className));   
    }
}

if(!function_exists('policyTo')) {
    function policyTo(string $target, string $className): string
    {
        return classNameMapper($className, classNameMap('policy', $target, $className));   
    }
}

if(!function_exists('clasNamespace')) {
    function clasNamespace($moduleName, $moduleType): string
    {
        [$moduleName, $moduleType] = array_map('ucfirst', array_map('strtolower', [$moduleName, $moduleType]));
        $moduleTypes = Str::plural($moduleType);
        $moduleType = $moduleType === 'Model' ? '' : $moduleType;
        return arcModuleNamespace() . "\\{$moduleName}\\{$moduleTypes}\\{$moduleName}{$moduleType}";
    }
}