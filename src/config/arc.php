<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ARC Package Config
    |--------------------------------------------------------------------------
    |
    | Default settings for ARC package.
    |
    */

    'modules_path' => app_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Global JS Dependencies
    |--------------------------------------------------------------------------
    */
    'dependencies' => [
        'jquery' => 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Libraries
    |--------------------------------------------------------------------------
    |
    | Standardized structure:
    | - requires : dependency keys
    | - assets   : css / js
    |
    */
    'libraries' => [

        'datatables' => [
            'requires' => ['jquery'],
            'assets' => [
                'js' => [
                    'https://cdn.datatables.net/2.3.4/js/dataTables.min.js',
                ],
                'plugins' => [
                    'buttons'        => 'https://cdn.datatables.net/buttons/3.1.2/js/dataTables.buttons.min.js',
                    'select'         => 'https://cdn.datatables.net/select/3.1.2/js/dataTables.select.min.js',
                    'searchpanes'    => 'https://cdn.datatables.net/searchpanes/2.3.0/js/dataTables.searchPanes.min.js',
                    'colreorder'     => 'https://cdn.datatables.net/colreorder/2.0.3/js/dataTables.colReorder.min.js',
                    'fixedcolumns'   => 'https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js',
                    'fixedheader'    => 'https://cdn.datatables.net/fixedheader/4.0.1/js/dataTables.fixedHeader.min.js',
                    'responsive'     => 'https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js',
                    'rowgroup'       => 'https://cdn.datatables.net/rowgroup/1.5.0/js/dataTables.rowGroup.min.js',
                    'rowreorder'     => 'https://cdn.datatables.net/rowreorder/1.5.0/js/dataTables.rowReorder.min.js',
                    'scroller'       => 'https://cdn.datatables.net/scroller/2.4.1/js/dataTables.scroller.min.js',
                    'staterestore'   => 'https://cdn.datatables.net/staterestore/1.4.1/js/dataTables.stateRestore.min.js',
                ],
            ],
        ],

        'sweetalert' => [
            'assets' => [
                'js' => [
                    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
                ],
            ],
        ],

        'select2' => [
            'requires' => ['jquery'],
            'assets' => [
                'css' => [
                    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                ],
                'js' => [
                    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                ],
            ],
        ],

        'fontawesome' => [
            'assets' => [
                'css' => [
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
                ],
            ],
        ],

        'chartjs' => [
            'assets' => [
                'js' => [
                    'https://cdn.jsdelivr.net/npm/chart.js',
                ],
            ],
        ],

        'leaflet' => [
            'assets' => [
                'css' => [
                    'https://unpkg.com/leaflet/dist/leaflet.css',
                ],
                'js' => [
                    'https://unpkg.com/leaflet/dist/leaflet.js',
                ],
            ],
        ],
    ],
];
