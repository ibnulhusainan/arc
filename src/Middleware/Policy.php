<?php

namespace IbnulHusainan\Arc\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Policy Middleware
 *
 * This middleware dynamically resolves the corresponding Policy class
 * and authorizes the given action for a specific model or model instance.
 *
 * Usage in routes:
 *  Route::get('/posts/{id?}', [PostController::class, 'show'])
 *      ->middleware('policy:view,post');
 */
class Policy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $action      The policy action (e.g. "view", "update", "delete")
     * @param  string  $model  The alias/model name (e.g. "post" → maps to App\Modules\Post\Models\Post)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $action, string $model)
    {
        $moduleName = ucfirst(class_basename($model));
        $policies = Gate::policies();
        
        $modelClass = class_exists($model) ? $model : clasNamespace($moduleName, 'model');
        $policyClass = $policies[$modelClass] ??  modelTo('policy', $model);
        
        if (!class_exists($policyClass)) return $next($request);

        abort_if(! class_exists($modelClass), 403, "Model {$modelClass} not found.");

        $modelInstance = app($modelClass);
        $primaryKeyName = $modelInstance->getKeyName();
        $primaryKeyValue = $request->route($primaryKeyName) ?? $request->input("{$primaryKeyName}.0") ?? $request->input($primaryKeyName);
        $action = $action ?? "viewAny";

        if (!is_null($primaryKeyValue)) {
            $modelInstance = $modelClass::findOrFail($primaryKeyValue);
        } else if (is_callable([$policyClass, "{$action}Any"])) {
            $action = "{$action}Any";
        }
   
        abort_if(
            ! Gate::forUser(auth()->user())->allows($action, $modelInstance),
            403,
            'Unauthorized action.'
        );

        return $next($request);
    }
}
