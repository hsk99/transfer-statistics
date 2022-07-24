<?php

return [
    'enable'   => false,
    'host'     => '127.0.0.1',
    'port'     => '9200',
    'scheme'   => 'https',
    'user'     => 'elastic',
    'pass'     => '',
    'ssl_cert' => '/etc/elasticsearch/certs/http_ca.crt',
    'index'    => 'tracing',
];
