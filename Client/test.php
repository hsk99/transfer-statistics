<?php

require_once __DIR__ . '/StatisticClient.php';

// 设置上报地址
StatisticClient::$remoteAddress = 'udp://127.0.0.1:8789';

$project  = 'test';       // 应用
$ip       = '127.0.0.1';  // 客户端IP
$transfer = 'test';       // 调用

// 计时
$unique = StatisticClient::tick($project, $ip, $transfer);

// 模拟运行
usleep(mt_rand(5, 200));

// 上报
$code    = mt_rand(100, 600);           // 状态码
$success = $code < 400 ? true : false;  // 是否成功
$details = json_encode([
    'project'  => $project,
    'ip'       => $ip,
    'transfer' => $transfer,
    'code'     => $code,
    'success'  => $success
], 320);  // 详情（JSON格式）
StatisticClient::report($unique, $project, $ip, $transfer, $success, $code, $details);
