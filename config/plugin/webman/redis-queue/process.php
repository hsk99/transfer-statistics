<?php
return [
    'statistic'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 4, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/statistic'
        ]
    ],
    'data'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 2, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/data'
        ]
    ]
];
