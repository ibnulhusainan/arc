<?php

namespace IbnulHusainan\Arc\Supports;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait Attributes
 *
 * Provides convenient access to PHP 8 Attributes defined
 * on the current controller method or controller class.
 *
 * This trait allows attributes to be accessed dynamically
 * using a `get{AttributeName}` property-style syntax.
 *
 * Example:
 * - #[Title('Dashboard')]
 * - $this->getTitle → 'Dashboard'
 *
 * Resolution order:
 * 1. Controller method attribute
 * 2. Controller class attribute
 */
trait Attributes
{
    /**
     * Resolve a specific attribute value from the current route context.
     *
     * The method first attempts to retrieve the attribute from
     * the active controller method. If not found, it falls back
     * to the controller class.
     *
     * @param string $attributeClass Fully-qualified attribute class name
     * @return mixed|null The attribute value or null if not found
     */
    public function getAttribute($attributeClass)
    {
        $route      = request()->route();
        $controller = $route->getController();
        $method     = $route->getActionMethod();

        // Attempt to resolve attribute from controller method
        $refMethod = new ReflectionMethod($controller, $method);
        $attrs     = $refMethod->getAttributes($attributeClass);

        // Fallback to controller-level attribute
        if (!$attrs) {
            $refController = new ReflectionClass($controller);
            $attrs = $refController->getAttributes($attributeClass);
        }

        return $attrs
            ? $attrs[0]->newInstance()->value
            : null;
    }

    /**
     * Magic property accessor for attribute-based getters.
     *
     * Intercepts property access that starts with `get` and attempts
     * to resolve a corresponding ARC attribute class dynamically.
     *
     * Example:
     * - Accessing `$this->getTitle`
     * - Resolves attribute class `IbnulHusainan\Arc\Attributes\Title`
     *
     * If the attribute class does not exist, the call is forwarded
     * to the parent `__get` implementation.
     *
     * @param string $key Property name being accessed
     * @return mixed|null
     */
    public function __get($key)
    {
        $startsWithGet = Str::startsWith($key, 'get');
        $className     = Str::after($key, 'get');
        $attributeClass = "IbnulHusainan\\Arc\\Attributes\\{$className}";

        if ($startsWithGet && class_exists($attributeClass)) {
            return $this->getAttribute($attributeClass);
        }

        return parent::__get($key);
    }
}
