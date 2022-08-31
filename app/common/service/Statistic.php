<?php

namespace app\common\service;

use support\Redis;

class Statistic
{
    /**
     * 执行统计
     *
     * @author HSK
     * @date 2022-08-26 11:53:35
     *
     * @return void
     */
    public static function run()
    {
        try {
            if (Redis::setNx('TransferCacheLock', 1)) {
                try {
                    Redis::expire('TransferCacheLock', 10);

                    $count = (int)Redis::lLen('TransferCache');
                    if (0 === $count) {
                        return;
                    } else if ($count > 100) {
                        $count = 100;
                    }

                    $transferList = Redis::lRange('TransferCache', 0, $count - 1);
                    Redis::lTrim('TransferCache', $count, -1);
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                    return;
                } finally {
                    Redis::del('TransferCacheLock');
                }
            } else {
                \Workerman\Timer::add(0.1, function () {
                    static::run();
                }, '', false);
                return;
            }


            foreach ($transferList as $transfer) {
                try {
                    $transfer = json_decode($transfer, true);

                    $transfer['project'] = str_replace([' ', ":"], '', trim($transfer['project']));
                    $transfer['success'] = (1 === $transfer['success']) ? true : false;

                    // 生成唯一追踪标识
                    $trace    = uniqid();
                    $transfer = ['trace' => $trace] + $transfer;

                    $dataInsert = json_encode([
                        'trace'     => $transfer['trace'],
                        'project'   => $transfer['project'],
                        'ip'        => $transfer['ip'],
                        'transfer'  => $transfer['transfer'],
                        'cost_time' => $transfer['costTime'],
                        'success'   => $transfer['success'] ? 1 : 0,
                        'code'      => $transfer['code'],
                        'details'   => $transfer['details'],
                        'day'       => date('Ymd', strtotime($transfer['time'])),
                        'time'      => $transfer['time'],
                    ], 320);
                    // 调用记录缓存
                    Redis::rPush('TracingInsertMySqlCache', $dataInsert);
                    // 调用记录添加至elasticsearch
                    if (config('elasticsearch.enable', false)) {
                        Redis::rPush('IndexInsertElasticSearchCache', $dataInsert);
                    }

                    // 总统计
                    static::totalStatistics($transfer);

                    // 应用统计
                    static::projectStatistics($transfer);

                    // 应用Client统计
                    static::projectClientStatistics($transfer);
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 总统计
     *
     * @author HSK
     * @date 2022-06-16 09:39:20
     *
     * @param array $data
     *
     * @return void
     */
    protected static function totalStatistics(array $data)
    {
        try {
            $project  = $data['project'];   // 应用
            $costTime = $data['costTime'];  // 消耗时长
            $success  = $data['success'];   // 状态
            $time     = $data['time'];      // 产生时间

            // 产生日期
            $day = date('Ymd', strtotime($time));

            // 记录应用
            Redis::hSetNx('TransferStatistics:project', $project, $project);

            // 记录整体统计（按天统计）
            // 耗时
            Redis::hIncrByFloat('TransferStatistics:statistic:cost', $day, $costTime);
            // 次数
            Redis::hIncrBy('TransferStatistics:statistic:count', $day, 1);
            // 成功次数
            Redis::hIncrBy('TransferStatistics:statistic:success_count', $day, $success ? 1 : 0);
            // 失败次数
            Redis::hIncrBy('TransferStatistics:statistic:error_count', $day, $success ? 0 : 1);

            // 设置过期时间
            $expireAt = strtotime($day) + 86400 + 46200;
            Redis::expireAt('TransferStatistics:project', $expireAt);
            Redis::expireAt('TransferStatistics:statistic:cost', $expireAt);
            Redis::expireAt('TransferStatistics:statistic:count', $expireAt);
            Redis::expireAt('TransferStatistics:statistic:success_count', $expireAt);
            Redis::expireAt('TransferStatistics:statistic:error_count', $expireAt);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 应用统计
     *
     * @author HSK
     * @date 2022-06-16 09:39:20
     *
     * @param array $data
     *
     * @return void
     */
    protected static function projectStatistics(array $data)
    {
        try {
            $project  = $data['project'];   // 应用
            $ip       = $data['ip'];        // IP
            $transfer = $data['transfer'];  // 调用
            $costTime = $data['costTime'];  // 消耗时长
            $success  = $data['success'];   // 状态
            $time     = $data['time'];      // 产生时间
            $code     = $data['code'];      // 状态码

            // 调用、IP 替换掉“:”，防止redis存储分类层级混乱，兼容IPv6
            $ipTemp       = str_replace(['::', ':'], '@', $ip);
            $transferTemp = str_replace(['::', ':'], '@', $transfer);

            // 产生日期
            $day = date('Ymd', strtotime($time));

            // 产生时间间隔（一分钟）
            $interval = date('YmdHi', ceil(strtotime($time) / 60) * 60);

            //////
            // 记录应用统计（按分钟统计，用于图表展示）
            //////
            // 次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:count:' . $day, $interval, 1);
            // 耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:cost:' . $day, $interval, $costTime);
            // 成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:success_count:' . $day, $interval, $success ? 1 : 0);
            // 失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:error_count:' . $day, $interval, $success ? 0 : 1);

            // 调用次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $interval, 1);
            // 调用耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $interval, $costTime);
            // 调用成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $interval, $success ? 1 : 0);
            // 调用失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $interval, $success ? 0 : 1);

            // IP次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp . ':' . $day, $interval, 1);
            // IP耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp . ':' . $day, $interval, $costTime);
            // IP成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp . ':' . $day, $interval, $success ? 1 : 0);
            // IP失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp . ':' . $day, $interval, $success ? 0 : 1);

            // 状态码次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code . ':' . $day, $interval, 1);
            // 状态码耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code . ':' . $day, $interval, $costTime);


            //////
            // 记录应用统计（按天统计）
            //////
            // 次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:count', $day, 1);
            // 耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:cost', $day, $costTime);
            // 成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:success_count', $day, $success ? 1 : 0);
            // 失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:error_count', $day, $success ? 0 : 1);

            // 调用次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp, $day, 1);
            // 调用耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp, $day, $costTime);
            // 调用成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp, $day, $success ? 1 : 0);
            // 调用失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp, $day, $success ? 0 : 1);

            // IP次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp, $day, 1);
            // IP耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp, $day, $costTime);
            // IP成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp, $day, $success ? 1 : 0);
            // IP失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp, $day, $success ? 0 : 1);

            // 状态码次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code, $day, 1);
            // 状态码耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code, $day, $costTime);

            // 记录应用IP（按天记录）
            Redis::hSetNx('TransferStatistics:project:' . $project . ':ip:' . $day, $ip, $ip);

            // 记录应用状态码（按天记录）
            Redis::hSetNx('TransferStatistics:project:' . $project . ':code:' . $day, $code, $code);

            // 记录应用调用（按天记录）
            Redis::hSetNx('TransferStatistics:project:' . $project . ':transfer:' . $day, $transfer, $transfer);

            // 设置过期时间
            $expireAt = strtotime($day) + 86400 + 46200;
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:count:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:cost:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:success_count:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:error_count:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:count', $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:cost', $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:success_count', $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:error_count', $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':ip:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':code:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':transfer:' . $day, $expireAt);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 应用Client统计
     *
     * @author HSK
     * @date 2022-06-16 09:39:20
     *
     * @param array $data
     *
     * @return void
     */
    protected static function projectClientStatistics(array $data)
    {
        try {
            $project  = $data['project'];   // 应用
            $client   = $data['ip'];        // Client（IP）
            $transfer = $data['transfer'];  // 调用
            $costTime = $data['costTime'];  // 消耗时长
            $success  = $data['success'];   // 状态
            $time     = $data['time'];      // 产生时间
            $code     = $data['code'];      // 状态码

            // 调用、IP 替换掉“:”，防止redis存储分类层级混乱，兼容IPv6
            $clientTemp   = str_replace(['::', ':'], '@', $client);
            $transferTemp = str_replace(['::', ':'], '@', $transfer);

            // 产生日期
            $day = date('Ymd', strtotime($time));

            // 产生时间间隔（一分钟）
            $interval = date('YmdHi', ceil(strtotime($time) / 60) * 60);

            //////
            // 记录应用统计（按分钟统计，用于图表展示）
            //////
            // 调用次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $interval, 1);
            // 调用耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $interval, $costTime);
            // 调用成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $interval, $success ? 1 : 0);
            // 调用失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $interval, $success ? 0 : 1);

            // 状态码次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_count:' . $code . ':' . $day, $interval, 1);
            // 状态码耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_cost:' . $code . ':' . $day, $interval, $costTime);


            //////
            // 记录应用Client统计（按天统计）
            //////
            // 调用次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_count:' . $transferTemp, $day, 1);
            // 调用耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_cost:' . $transferTemp, $day, $costTime);
            // 调用链成功次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_success:' . $transferTemp, $day, $success ? 1 : 0);
            // 调用链失败次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_error:' . $transferTemp, $day, $success ? 0 : 1);

            // 状态码次数
            Redis::hIncrBy('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_count:' . $code, $day, 1);
            // 状态码耗时
            Redis::hIncrByFloat('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_cost:' . $code, $day, $costTime);

            // 记录应用Client状态码（按天记录）
            Redis::hSetNx('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':code:' . $day, $code, $code);

            // 记录应用Client调用（按天记录）
            Redis::hSetNx('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':transfer:' . $day, $transfer, $transfer);

            // 设置过期时间
            $expireAt = strtotime($day) + 86400 + 46200;
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_count:' . $code . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_cost:' . $code . ':' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_count:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_cost:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_success:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_error:' . $transferTemp, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_count:' . $code, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':statistic:code_cost:' . $code, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':code:' . $day, $expireAt);
            Redis::expireAt('TransferStatistics:project:' . $project . ':client:' . $clientTemp . ':transfer:' . $day, $expireAt);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }
}
