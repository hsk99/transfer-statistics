<?php

if (is_file(config_path() . '/system.php')) {
    $system = include config_path() . '/system.php';
} else {
    $system = [];
}

if (is_file(config_path() . '/app.php')) {
    $app = include config_path() . '/app.php';
} else {
    $app = [];
}

return [
    'enable'   => true,
    'notice'   => true,
    'interval' => 30,
    'project'  => $app['project'] ?? 'transfer-statistics',
    'email'    => !empty($system['exception_email']) ? array_values($system['exception_email']) : [],
];
