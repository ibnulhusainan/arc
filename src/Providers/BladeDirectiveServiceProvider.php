<?php

namespace IbnulHusainan\Arc\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

/**
 * BladeDirectiveServiceProvider
 *
 * Registers custom Blade directives used by the ARC package.
 *
 * Currently provides:
 * - @arcScripts : Dynamically injects CSS and JS assets
 *   based on ARC library configuration and exposes
 *   module routes to the frontend.
 */
class BladeDirectiveServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->arc();
    }

    /**
     * Register ARC-related Blade directives.
     *
     * @return void
     */
    private function arc(): void
    {
        /**
         * @arcScripts Blade directive
         *
         * Usage examples:
         *  - @arcScripts
         *  - @arcScripts('datatables')
         *  - @arcScripts('datatables[buttons,select],select2')
         *
         * Behavior:
         * - Loads configured CSS and JS assets based on library names.
         * - Automatically resolves and injects required dependencies.
         * - Supports optional plugin loading via bracket notation.
         * - Prepends assets into the "styles" and "scripts" Blade stacks.
         * - Exposes ARC module routes to JavaScript via `window.routes`.
         *
         * @param  string|null  $scripts
         * @return string
         */
        Blade::directive('arcScripts', function ($scripts = null) {
            $scripts = $scripts ?: 'null';

            return <<<PHP
                <?php
                    echo '<script>window.routes = ' . json_encode(arcModuleRoutes()) . '</script>';

                    [\$css, \$js] = arcScripts($scripts);

                    if (!empty(\$css)) {
                        app('view')->startPrepend('styles');
                        echo implode('', array_map(
                            fn(\$s) => '<link rel="stylesheet" href="' . \$s . '">',
                            (array) \$css
                        ));
                        app('view')->stopPrepend();
                    }

                    if (!empty(\$js)) {
                        app('view')->startPrepend('scripts');
                        echo implode('', array_map(
                            fn(\$s) => '<script src="' . \$s . '"></script>',
                            (array) \$js
                        ));
                        app('view')->stopPrepend();
                    }
                ?>
            PHP;
        });
    }
}
