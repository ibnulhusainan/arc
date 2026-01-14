<?php

namespace IbnulHusainan\Arc\Events;

/**
 * Event fired after a module's table rollback is executed.
 */
class TableRolledBack
{
    /**
     * @var string Module namespace or path
     */
    public string $module;

    public function __construct(string $module)
    {
        $this->module = $module;
    }
}
