<?php

namespace support\bootstrap\LogFormatter;

use \Monolog\Formatter\JsonFormatter;

class RequestFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        return json_encode($record['context'], 320) . "\n";
    }
}
