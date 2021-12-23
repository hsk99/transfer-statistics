# TransferStatistics

TransferStatistics 使用[webman](https://github.com/walkor/webman)开发的一个应用监控系统，用于查看应用调用记录、请求量、调用耗时、调用分析等。

系统使用 `UDP` 接收上报数据；使用 `Redis` 存储、汇总数据


# 所需环境

PHP版本不低于7.3，并安装 Redis 拓展


# 安装

## composer安装

创建项目

`composer create-project hsk99/transfer-statistics`

## 下载安装

1、下载 或 `git clone https://github.com/hsk99/transfer-statistics`

2、执行命令 `composer install`

## 配置

1、config/redis.php 设置 Redis

2、config/app.php 设置 登录用户名、密码

3、config/server.php 设置 WebServer

4、config/process.php 设置 采集服务

## 运行

执行命令 `php start.php start`

## 查看统计

浏览器访问 `http://ip地址:8788`

## 上报数据

使用类库：Client/StatisticClient.php

1、webman 中间件使用

config/app.php 增加 `statisticAddress` 项，设置 上报地址

```
<?php

namespace app\common\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class Statistic implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $ip         = $request->getRealIp($safe_mode = true);
        $controller = $request->controller;
        $action     = $request->action;
        $transfer   = $controller . '::' . $action;

        // 开始计时
        $unique = StatisticClient::tick('project', $ip, $transfer);

        $response = $next($request);

        $code    = $response->getStatusCode();
        $success = $code < 400;
        $details = [
            'ip'              => $request->getRealIp($safe_mode = true) ?? '',   // 请求客户端IP
            'url'             => $request->fullUrl() ?? '',                      // 请求URL
            'method'          => $request->method() ?? '',                       // 请求方法
            'request_param'   => $request->all() ?? [],                          // 请求参数
            'request_header'  => $request->header() ?? [],                       // 请求头
            'cookie'          => $request->cookie() ?? [],                       // 请求cookie
            'session'         => $request->session()->all() ?? [],               // 请求session
            'response_code'   => $response->getStatusCode() ?? '',               // 响应码
            'response_header' => $response->getHeaders() ?? [],                  // 响应头
        ];
        // 数据上报
        StatisticClient::report($unique, 'project', $ip, $transfer, $success, $code, json_encode($details, 320));

        return $response;
    }
}

```

2、通用使用

```
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

```