<?php

namespace IbnulHusainan\Arc\Services\Modules;

use IbnulHusainan\Arc\Services\Modules\ModuleContext;
use IbnulHusainan\Arc\Supports\ConsoleOutput;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleGenerator
{
    use ConsoleOutput;

    protected object $opts;

    public function __construct(protected ModuleContext $context, ?array $opts = null)
    {
        $this->opts = (object) $opts;
    }

    /** Generate migration file */
    public function makeMigration(): void
    {
        $this->lineInfo("Creating migration file.....");

        $stubContent = $this->getStub('Migration');
        $stubContent = $this->replaceStubVariables($stubContent);

        $fileName = now()->format("Y_m_d_His") . "_create_{$this->context->tableName}_table.php";
        $filePath = database_path("migrations/{$fileName}");

        if ($stubContent && !File::exists($filePath)) {
            File::put($filePath, $stubContent);
        }

        $migrationFilePath = ltrim(str_replace(base_path(), '', $filePath), DIRECTORY_SEPARATOR);
        $this->lineInfo("Migration *[{$migrationFilePath}]* created successfully.");
        $this->lineInfo("Edit the migration file then run *php artisan migrate*");
    }

    /** Generate all module files */
    public function makeModules(): void
    {
        $context = $this->context;

        $stubs = collect($this->stubsMap());
    
        if (!empty($context->moduleExcept)) {
            $stubs = $stubs->except($context->moduleExcept);
        }
    
        if (!empty($context->moduleOnly)) {
            $stubs = $stubs->only($context->moduleOnly);
        }

        if($stubs->isEmpty()) {
            $this->lineSpace();
            $this->lineError("No module selected");
            return;
        }
        
        $modulesList = $stubs->keys()->implode(', ');
        $this->lineSpace();
        $this->lineNew('ARC Module Generator');
        $this->lineNew('---------------------');
        $this->lineSpace();
        $this->lineNew("> Modules     : {$modulesList}");
        $this->lineNew("> Output Path : {$context->moduleBasePath}");
        $this->lineSpace();
    
        foreach ($stubs as $stubName => $stubPath) {
            $fullPath = "{$context->moduleBasePath}/{$stubPath}";
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }

            $this->generateModuleFromStub($stubPath, $stubName);
            $this->lineOk("{$stubName} created!");
        }

        cmdCreated($context->modulePath);
    }

    /** Generate a single stub file */
    private function generateModuleFromStub(string $stubPath, string $stubName): void
    {
        $force = $this->opts?->force;
        $context = $this->context;
        $stubContent = $this->getStub($stubName);

        if (is_array($stubContent)) {
            if($stubName == 'Request') {
                $stubPath = "{$stubName}s";
            }

            foreach ($stubContent as $fileName => $content) {
                if($stubName === 'View') {
                    $fileName .= '.blade';
                } else {
                    $fileName = "{$fileName}{$context->moduleName}{$stubName}";
                }
    
                $this->writeStubFile("{$context->moduleBasePath}/{$stubPath}/{$fileName}.php", $this->replaceStubVariables($content), $force);
            }
        } else {
            $fileName = $stubName === 'Model' ? $context->moduleName : $context->moduleName . $stubName;
            $this->writeStubFile("{$context->moduleBasePath}/{$stubPath}/{$fileName}.php", $this->replaceStubVariables($stubContent), $force);
        }
    }

    /** Write stub content to file if force or file not exists */
    private function writeStubFile(string $path, string $content, bool $force = false): void
    {
        if ($content && ($force || !File::exists($path))) {
            File::put($path, $content);
        }
    }

    /** Replace placeholders in stub */
    private function replaceStubVariables(string $stub): string
    {
        $context = $this->context;
        $schema = $context->schema ?? null;
        if($schema) {
            $schema->hasSoftDeletes = !empty(array_filter($schema->timestamps, fn($col) => str_contains($col, 'delete')));
            $schema->hasTimestamps = !empty(array_filter($schema->timestamps, fn($col) => !str_contains($col, 'delete')));
            $schema->fillable = array_keys($schema->columns);
        }

        $replacements = [
            // global
            '{{modulePath}}'      => $context->modulePath,
            '{{moduleName}}'      => $context->moduleName,
            '{{moduleVar}}'       => strtolower($context->moduleName),
            '{{moduleNamespace}}' => $context->moduleNamespace,
            '{{routePrefix}}'     => $context->routePrefix,
            '{{tableName}}'       => $context->tableName,

            // model
            '{{useClass}}'        => $this->generateUseClasses($schema),
            '{{traitModul}}'      => $this->generateTraits($schema),
            '{{primaryKey}}'      => $this->generatePrimaryKey($schema),
            // '{{routePrimaryKey}}' => $this->generateRoutePrimaryKey($schema),
            '{{keyType}}'         => $this->generateKeyType($schema),
            '{{incrementing}}'    => $this->generateIncrementing($schema),
            '{{timestamps}}'      => $this->generateTimestamps($schema),
            '{{timestampsField}}' => $this->generateTimestampsField($schema),
            '{{fillable}}'        => $this->generateFillable($schema),
            '{{casts}}'           => $this->generateCasts($schema),
            '{{listColumns}}'     => $this->generateViewsColumns($schema),
            '{{formColumns}}'     => $this->generateViewsColumns($schema),

            // request
            '{{saveRules}}'       => $this->generateSaveRules($schema),
            '{{deleteRules}}'     => $this->generateDeleteRules($schema),

            // policy
            '{{userModel}}'       => $this->getUserModel(),
        ];

        $stub = str_replace(array_keys($replacements), array_values($replacements), $stub);

        // Clean multiple empty lines and trim
        $stub = preg_replace("/^\s*$/m", "", $stub);
        $stub = preg_replace("/\n{2,}/", "\n\n", $stub);
        
        return trim($stub) . "\n";
    }

    /** Helpers for generating model placeholders */
    private function generateUseClasses(?object $schema): string
    {
        $classes = [];
        if ($schema?->hasSoftDeletes) $classes[] = "use Illuminate\\Database\\Eloquent\\SoftDeletes;";
        if ($schema?->keyType === 'string') $classes[] = "use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;";
        return $classes ? "\n" . implode("\n", $classes) : '';
    }

    private function generateTraits(?object $schema): string
    {
        $traits = [];
        if ($schema?->hasSoftDeletes) $traits[] = 'SoftDeletes';
        if ($schema?->keyType === 'string') $traits[] = 'HasUuids';
        return $traits ? "use " . implode(', ', $traits) . ";\n    " : '';
    }

    private function generatePrimaryKey(?object $schema): string
    {
        return ($schema?->primaryKey && $schema->primaryKey !== 'id') ? "\n    protected \$primaryKey = '{$schema->primaryKey}';" : '';
    }

    private function generateRoutePrimaryKey(?object $schema): string
    {
        return ($schema?->primaryKey && $schema->primaryKey !== 'id') ? ", ['id' => '{$schema->primaryKey}']" : '';
    }

    private function generateKeyType(?object $schema): string
    {
        return ($schema?->keyType && $schema->keyType !== 'int') ? "\n    protected \$keyType = '{$schema->keyType}';" : '';
    }

    private function generateIncrementing(?object $schema): string
    {
        return ($schema?->incrementing === false) ? "\n    public \$incrementing = false;" : '';
    }

    private function generateTimestamps(?object $schema): string
    {
        return (!$schema?->hasTimestamps) ? "\n    public \$timestamps = false;" : '';
    }

    private function generateTimestampsField(?object $schema): string
    {
        if (empty($schema?->timestamps)) {
            return '';
        }
    
        $timestamps = collect($schema->timestamps);
    
        $created = $timestamps->first(fn($col) => str_contains($col, 'create'));
        $updated = $timestamps->first(fn($col) => str_contains($col, 'update'));
        $deleted = $timestamps->first(fn($col) => str_contains($col, 'delete'));
    
        $lines = [];
    
        if ($created && $created !== 'created_at') {
            $lines[] = "    const CREATED_AT = '{$created}';";
        }
        elseif (!$created) {
            $lines[] = "    const CREATED_AT = null;";
        }
    
        if ($updated && $updated !== 'updated_at') {
            $lines[] = "    const UPDATED_AT = '{$updated}';";
        }
        elseif (!$updated) {
            $lines[] = "    const UPDATED_AT = null;";
        }
    
        if ($deleted && $deleted !== 'deleted_at') {
            $lines[] = "    const DELETED_AT = '{$deleted}';";
        }
        elseif (!$deleted) {
            $lines[] = "    const DELETED_AT = null;";
        }
    
        return $lines ? "\n" . implode("\n", $lines) . "\n" : '';
    }

    private function generateFillable(?object $schema): string
    {
        return $schema?->fillable ? "'" . implode("',\n        '", $schema->fillable) . "'" : '';
    }

    private function generateCasts(?object $schema): string
    {
        if (!$schema?->columns) return '';
    
        $casts = [];
    
        foreach ($schema->columns as $column => $info) {
            // Skip primary key and timestamps
            if ($column === $schema->primaryKey || in_array($column, $schema->timestamps)) {
                continue;
            }
    
            if (!empty($info['enum'])) {
                $casts[$column] = 'string';
            } elseif (!empty($info['type'])) {
                switch ($info['type']) {
                    case 'int':
                    case 'bigint':
                    case 'smallint':
                        $casts[$column] = 'integer';
                        break;
                    case 'decimal':
                    case 'float':
                    case 'double':
                        $casts[$column] = 'float';
                        break;
                    case 'boolean':
                        $casts[$column] = 'boolean';
                        break;
                    case 'json':
                    case 'array':
                        $casts[$column] = 'array';
                        break;
                    case 'date':
                    case 'datetime':
                    case 'timestamp':
                        $casts[$column] = 'datetime';
                        break;
                    default:
                        $casts[$column] = 'string';
                }
            }
        }
    
        $lines = [];
        foreach ($casts as $col => $type) {
            $lines[] = "'{$col}' => '{$type}'";
        }
    
        return $lines ? implode(",\n        ", $lines) : '';
    }

    private function generateViewsColumns(?object $schema): string
    {
        return  $schema?->fillable ? implode(",\n        ", array_map(fn($c) => "'$c' => '" . Str::title(str_replace('_', ' ', $c)) . "'", $schema->fillable)) : '';
    }

    /** Helpers for generating request placeholders */
    private function generateSaveRules(?object $schema): string
    {
        if (!$schema?->columns) return '';
    
        $rules = [
            // Primary Key
            $schema->primaryKey => "nullable|exists:{$schema->table},{$schema->primaryKey}"
        ];
    
        foreach ($schema->columns as $column => $info) {
            $colRules = [];

            // Nullable or required
            $colRules[] = $info['nullable'] ? 'nullable' : 'required';

            // Numeric
            if(in_array($info['type'], ['tinyint', 'int', 'bigint', 'smallint', 'decimal', 'float', 'double'])) {
                $colRules[] = 'numeric';
            }
            
            // Date
            if(in_array($info['type'], ['date', 'datetime', 'timestamp'])) {
                $colRules[] = 'date';
            }

            // Max length
            if (!empty($info['maxLength'])) {
                $colRules[] = "max:{$info['maxLength']}";
            }
    
            // Enum
            if (!empty($info['enum']) && is_array($info['enum'])) {
                $enumValues = implode(',', $info['enum']);
                $colRules[] = "in:{$enumValues}";
            }
    
            $rules[$column] = implode('|', $colRules);
        }
    
        // Format array as string
        $rulesStr = [];
        foreach ($rules as $col => $rule) {
            $rulesStr[] = "'{$col}' => '{$rule}'";
        }
    
        return implode(",\n			", $rulesStr);
    }

    private function generateDeleteRules(?object $schema): string
    {
        if (!$schema?->columns) return '';
        
        $keyType = $schema->keyType == 'int' ? 'integer' : 'string';

        $rules = [
            // Primary Key
            $schema->primaryKey       => "required|array",
            "{$schema->primaryKey}.*" => "{$keyType}|exists:{$schema->table},{$schema->primaryKey}"
        ];
    
        // Format array as string
        $rulesStr = [];
        foreach ($rules as $col => $rule) {
            $rulesStr[] = "'{$col}' => '{$rule}'";
        }
    
        return implode(",\n			", $rulesStr);
    }
    /** Helpers for generating policy placeholders */
    private function getUserModel()
    {
        $userModel = config('auth.providers.users.model');

        return !str_ends_with($userModel, 'User') ? "{$userModel} as User" : $userModel;
    }

    /** Load stub content */
    private function getStub(string $type)
    {
        $stubPath = base_path('vendor/ibnulhusainan/arc/stubs');

        $stubs = [
            'Controller' => File::get($stubPath . '/controller.stub'),
            'Request'    => [
                'Save'   => File::get($stubPath . '/requests/save.stub'),
                'Delete' => File::get($stubPath . '/requests/delete.stub'),
            ],
            'Service'    => File::get($stubPath . '/service.stub'),
            'Datatable'  => File::get($stubPath . '/datatable.stub'),
            'Repository' => File::get($stubPath . '/repository.stub'),
            'Model'      => File::get($stubPath . '/model.stub'),
            'Policy'     => File::get($stubPath . '/policy.stub'),
            'Route'      => File::get($stubPath . '/route.stub'),
            'Email'      => File::get($stubPath . '/email.stub'),
            'View'       => [
                'list'   => File::get($stubPath . '/views/list.stub'),
                'form'   => File::get($stubPath . '/views/form.stub'),
                'detail' => File::get($stubPath . '/views/detail.stub'),
            ],
            'Migration' => File::get($stubPath . '/migration.stub'),
        ];

        return $stubs[$type] ?? '';
    }

    /** Map folders to stubs */
    private function stubsMap(): array
    {
        return [
            'Controller' => 'Controllers',     
            'Request'    => 'Requests',        
            'Service'    => 'Services',        
            'Datatable'  => 'Datatables',      
            'Repository' => 'Repositories',    
            'Model'      => 'Models',          
            'Policy'     => 'Policies',        
            'Route'      => 'Routes',          
            'Email'      => 'Templates/Emails',
            'View'       => 'Templates/Views', 
        ];
    }
}
