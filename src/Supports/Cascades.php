<?php

namespace IbnulHusainan\Arc\Supports;

/**
 * Trait Cascades
 *
 * Provides automatic cascading behavior for Eloquent model events.
 *
 * This trait hooks into the model lifecycle and allows related
 * records to be automatically deleted when the parent model
 * is deleted.
 *
 * To enable cascading deletes, the model must define a
 * `$cascadeDeletes` property containing relationship method names.
 *
 * Example:
 * protected array $cascadeDeletes = ['comments', 'attachments'];
 */
trait Cascades
{
    /**
     * Bootstrap cascade-related model event listeners.
     *
     * This method is automatically called by Laravel when
     * the model boots the trait.
     *
     * Supported events:
     * - deleting : deletes related models defined in `$cascadeDeletes`
     *
     * @return void
     */
    protected static function bootCascades()
    {
        /**
         * Handle model updating event.
         *
         * Reserved for future cascade update logic.
         */
        static::updating(function ($model) {
            // Intentionally left blank
        });

        /**
         * Handle model deleting event.
         *
         * Deletes related records defined in `$cascadeDeletes`
         * before the parent model is removed.
         */
        static::deleting(function ($model) {

            if (!property_exists($model, 'cascadeDeletes')) {
                return;
            }

            foreach ($model->cascadeDeletes as $relation) {
                if (!method_exists($model, $relation)) {
                    continue;
                }

                $related = $model->{$relation}();

                if (!$related) {
                    continue;
                }

                $related->cursor()->each(function ($item) {
                    $item->delete();
                });
            }
        });
    }
}
