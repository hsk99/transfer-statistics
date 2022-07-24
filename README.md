
# TransferStatistics

> TransferStatistics 使用[webman](https://github.com/walkor/webman)开发的一个应用监控系统，用于查看应用调用记录、请求量、调用耗时、调用分析等。

> 系统使用 `HTTP` 接收上报数据；使用 `Redis` 进行数据汇总统计；使用 `MySql` 存储统计数据和上报信息


# 所需环境

PHP版本不低于7.2，并安装 Redis 拓展


# 安装

## composer安装

创建项目

`composer create-project hsk99/transfer-statistics`

## 下载安装

1、下载 或 `git clone https://github.com/hsk99/transfer-statistics`

2、执行命令 `composer install`

## 导入数据库

- sql文件位置：` database/transfer.sql `

## 配置修改

1、修改文件 `config/redis.php` 设置 Redis

2、修改文件 `config/server.php` 设置 HTTP

3、修改目录 `config/plugin/webman/redis-queue/` 设置 RedisQueue 相关信息

4、修改文件 ` config/thinkorm.php ` 设置 MySql 相关信息


# 运行

执行命令 `php start.php start`


# 查看统计

- 浏览器访问 `http://ip地址:8788`

- 默认账号：` admin `

- 默认密码：` admin888 `

- 相关信息可在 ` 系统管理--系统设置 ` 中进行设置


# 上报数据

- [webman](https://github.com/walkor/webman) 使用 [webman-statistic](https://github.com/hsk99/webman-statistic) 插件

- 其他框架使用，TP6中间件示例

```php
<?php

declare(strict_types=1);

namespace app\middleware;

class Transfer
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $startTime = microtime(true);  // 开始时间
        $project   = 'tp6';            // 应用名
        $ip        = '127.0.0.1';      // 请求IP
        $transfer  = 'test';           // 调用入口

        $response = $next($request);

        $finishTime = microtime(true);           // 结束时间
        $costTime   = $finishTime - $startTime;  // 运行时长

        $code    = mt_rand(2, 5) * 100;  // 状态码
        $success = $code < 400;          // 是否成功
        // 详细信息，自定义设置
        $details = [
            'time'     => date('Y-m-d H:i:s.', (int)$startTime) . substr((string)$startTime, 11),   // 请求时间（包含毫秒时间）
            'run_time' => $costTime,                                                                // 运行时长
            // .....
        ];

        // 执行上报
        try {
            // 数据打包 多条 换行 隔开
            $data = json_encode([
                'time'     => date('Y-m-d H:i:s.', (int)$startTime) . substr((string)$startTime, 11),
                'project'  => $project,
                'ip'       => $ip,
                'transfer' => $transfer,
                'costTime' => $costTime,
                'success'  => $success ? 1 : 0,
                'code'     => $code,
                'details'  => json_encode($details, 320),
            ], 320) . "\n";

            $client = new \GuzzleHttp\Client(['verify' => false]);
            $client->post(
                // 上报地址
                'http://127.0.0.1:8788/report/statistic/transfer',
                [
                    'headers' => [
                        // 上报认证，不设置默认为当前年份的md5值
                        'authorization' => md5(date('Y'))
                    ],
                    'form_params' => [
                        // 上报数据
                        'transfer' => $data
                    ],
                ]
            );
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $response;
    }
}
```
