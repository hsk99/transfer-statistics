<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */


return [
    // File update detection and automatic reload
    'monitor' => [
        'handler' => process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitor_dir' => [
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
                base_path() . '/extend',
            ],
            // Files with these suffixes will be monitored
            'monitor_extensions' => [
                'php', 'html', 'htm', 'env'
            ]
        ],
        'bootstrap' => []
    ],
    'statistic' => [
        'handler' => process\Statistic::class,
        'count'   => 1,
    ],
    'sync.statistics.to.mysql' => [
        'handler' => process\SyncStatisticsToMySql::class,
        'count'   => 1,
    ],
    'tracing.insert.mysql' => [
        'handler' => process\TracingInsertMySql::class,
        'count'   => 1,
    ],
    'index.insert.elasticsearch' => [
        'handler' => process\IndexInsertElasticSearch::class,
        'count'   => 1,
    ],
    'sync.index.to.elasticsearch' => [
        'handler' => process\SyncIndexToElasticSearch::class,
        'count'   => 1,
    ],
];
