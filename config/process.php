<?php

return [
    // File update detection and automatic reload
    'monitor' => [
        'handler'     => process\FileMonitor::class,
        'reloadable'  => false,
        'constructor' => [
            // Monitor these directories
            'monitor_dir' => [
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
                base_path() . '/expand',
                base_path() . '/Protocols',
            ],
            // Files with these suffixes will be monitored
            'monitor_extensions' => [
                'php', 'html', 'htm', 'env'
            ]
        ],
        'bootstrap' => []
    ],
    // Statistic
    'statistic' => [
        'handler'   => process\Statistic::class,
        'listen'    => 'statistic://0.0.0.0:8789',
        'count'     => 1,
        'transport' => 'udp',
        'bootstrap' => []
    ],
];
