<?php

namespace IbnulHusainan\Arc\Database\Migrations;

use Illuminate\Database\Migrations\Migration as LaravelMigration;
use Illuminate\Support\Facades\Event;
use IbnulHusainan\Arc\Events\TableMigrated;
use IbnulHusainan\Arc\Events\TableRolledBack;

/**
 * Base Migration class for Arc package.
 * 
 * - Extends Laravel's default Migration class.
 * - Automatically dispatches `TableMigrated` and `TableRolledBack` events
 *   when `up()` and `down()` are executed.
 * - Provides abstract `migrate()` and `rollback()` methods for child classes
 *   to implement instead of overriding `up()` and `down()` directly.
 * 
 * Usage:
 *   - Extend this class in your migration file.
 *   - Define `$table` and `$module` properties for event context.
 *   - Implement `migrate()` for creating/updating schema.
 *   - Implement `rollback()` for reversing schema changes.
 */
abstract class Migration extends LaravelMigration
{
    /**
     * @var string|null The name of the database table handled by this migration
     */
    protected ?string $table = null;

    /**
     * @var string|null The module namespace or path associated with this migration
     */
    protected ?string $module = null;

    /**
     * Run the migrations and dispatch a TableMigrated event.
     */
    public function up(): void
    {
        $this->migrate();

        if ($this->table && $this->module) {
            Event::dispatch(new TableMigrated($this->table, $this->module));
        }
    }

    /**
     * Reverse the migrations and dispatch a TableRolledBack event.
     */
    public function down(): void
    {
        $this->rollback();

        if ($this->table && $this->module) {
            Event::dispatch(new TableRolledBack($this->table, $this->module));
        }
    }

    /**
     * Child classes must implement migration logic here.
     */
    abstract protected function migrate(): void;

    /**
     * Child classes must implement rollback logic here.
     */
    abstract protected function rollback(): void;
}
