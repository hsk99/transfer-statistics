<?php
return [
    'statistic' => [
        'host' => 'redis://127.0.0.1:6379',
        'options' => [
            'auth'          => 'hsk99',   // 密码，字符串类型，可选参数
            'db'            => 0,         // 数据库
            'max_attempts'  => 5,         // 消费失败后，重试次数
            'retry_seconds' => 5,         // 重试间隔，单位秒
        ]
    ],
    'data' => [
        'host' => 'redis://127.0.0.1:6379',
        'options' => [
            'auth'          => 'hsk99',   // 密码，字符串类型，可选参数
            'db'            => 0,         // 数据库
            'max_attempts'  => 5,         // 消费失败后，重试次数
            'retry_seconds' => 5,         // 重试间隔，单位秒
        ]
    ],
];
