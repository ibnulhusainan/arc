<?php

namespace IbnulHusainan\Arc\Attributes;

use Attribute;

/**
 * Title Attribute
 *
 * Defines a human-readable title for a class or method.
 *
 * This attribute is typically used to provide metadata for:
 * - Page titles
 * - UI headers
 * - Breadcrumb labels
 * - Module or feature naming
 *
 * The value can be accessed via PHP Reflection
 * during runtime to dynamically determine the title.
 *
 * Example usage:
 *
 *  #[Title('User Management')]
 *  class UserController {}
 *
 *  #[Title('Create User')]
 *  public function create() {}
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Title
{
    /**
     * The title value.
     *
     * @var string
     */
    public string $value;

    /**
     * Create a new Title attribute instance.
     *
     * @param string $value The title to be assigned.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
