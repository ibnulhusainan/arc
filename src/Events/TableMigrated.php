<?php

namespace IbnulHusainan\Arc\Events;

/**
 * Event fired after a module's table migration is executed.
 */
class TableMigrated
{
    /**
     * @var string Database table name
     */
    public string $table;

    /**
     * @var string Module namespace or path
     */
    public string $module;

    public function __construct(string $table, string $module)
    {
        $this->table = $table;
        $this->module = $module;
    }
}
