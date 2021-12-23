<?php

return [
    'listen'               => 'http://0.0.0.0:8788',
    'transport'            => 'tcp',
    'context'              => [],
    'name'                 => 'server',
    'count'                => 1,
    'user'                 => '',
    'group'                => '',
    'pid_file'             => runtime_path() . '/master.pid',
    'stdout_file'          => runtime_path() . '/stdout.log',
    'log_file'             => runtime_path() . '/master.log',
    'max_request'          => 1000000,
    'max_package_size'     => 100 * 1024 * 1024
];
