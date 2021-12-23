<?php

namespace process;

use support\Redis;

/**
 * 统计
 *
 * @author HSK
 * @date 2021-12-12 22:26:59
 */
class Statistic
{
    /**
     * 接收上报数据
     *
     * @author HSK
     * @date 2021-12-12 22:27:17
     *
     * @param \Workerman\Connection\UdpConnection $connection
     * @param array $message
     *
     * @return void
     */
    public function onMessage(\Workerman\Connection\UdpConnection $connection, $message)
    {
        // 生成唯一追踪标识
        $trace = uniqid();
        $message = ['trace' => $trace] + $message;

        // 总统计
        $this->totalStatistics($message);

        // 项目（应用）统计
        $this->projectStatistics($message);

        // 项目（应用）Client统计
        $this->projectClientStatistics($message);
    }

    /**
     * 总统计
     *
     * @author HSK
     * @date 2021-12-13 13:42:44
     *
     * @param array $data
     *
     * @return void
     */
    protected function totalStatistics(array $data)
    {
        $trace    = $data['trace'];     // 追踪标识
        $project  = $data['project'];   // 项目（应用）
        $ip       = $data['ip'];        // IP
        $transfer = $data['transfer'];  // 调用
        $costTime = $data['costTime'];  // 消耗时长
        $success  = $data['success'];   // 状态
        $time     = $data['time'];      // 产生时间
        $code     = $data['code'];      // 状态码
        $details  = $data['details'];   // 详情

        // 产生日期
        $day = date('Ymd', $time);

        // 记录项目（应用）
        Redis::hSetNx('project', $project, $project);

        // 记录调用（按天存储）
        Redis::hSet('tracing:' . $day, $trace, json_encode($data, 320));

        // 记录追踪标识（按天存储）
        Redis::lPush('trace:' . $day, $trace);

        // 记录整体统计（按天统计）
        // 耗时
        Redis::hIncrByFloat('statistic:cost', $day, $costTime);
        // 次数
        Redis::hIncrBy('statistic:count', $day, 1);
        // 成功次数
        Redis::hIncrBy('statistic:success_count', $day, $success ? 1 : 0);
        // 失败次数
        Redis::hIncrBy('statistic:error_count', $day, $success ? 0 : 1);
    }

    /**
     * 项目（应用）统计
     *
     * @author HSK
     * @date 2021-12-13 13:50:30
     *
     * @param array $data
     *
     * @return void
     */
    protected function projectStatistics(array $data)
    {
        $trace    = $data['trace'];     // 追踪标识
        $project  = $data['project'];   // 项目（应用）
        $ip       = $data['ip'];        // IP
        $transfer = $data['transfer'];  // 调用
        $costTime = $data['costTime'];  // 消耗时长
        $success  = $data['success'];   // 状态
        $time     = $data['time'];      // 产生时间
        $code     = $data['code'];      // 状态码
        $details  = $data['details'];   // 详情

        // 调用、IP 替换掉“:”，防止redis存储分类层级混乱，兼容IPv6
        $ipTemp       = str_replace(['::', ':'], '@', $ip);
        $transferTemp = str_replace(['::', ':'], '@', $transfer);

        // 产生日期
        $day = date('Ymd', $time);

        // 产生时间间隔（五分钟）
        $interval = date('YmdHi', ceil($time / 300) * 300);

        //////
        // 记录项目（应用）统计（按分钟统计，用于图表展示）
        //////
        // 次数
        Redis::hIncrBy('project:' . $project . ':statistic:count:' . $day, $interval, 1);
        // 耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:cost:' . $day, $interval, $costTime);
        // 成功次数
        Redis::hIncrBy('project:' . $project . ':statistic:success_count:' . $day, $interval, $success ? 1 : 0);
        // 失败次数
        Redis::hIncrBy('project:' . $project . ':statistic:error_count:' . $day, $interval, $success ? 0 : 1);

        // 调用次数
        Redis::hIncrBy('project:' . $project . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $interval, 1);
        // 调用耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $interval, $costTime);
        // 调用成功次数
        Redis::hIncrBy('project:' . $project . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $interval, $success ? 1 : 0);
        // 调用失败次数
        Redis::hIncrBy('project:' . $project . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $interval, $success ? 0 : 1);

        // 状态码次数
        Redis::hIncrBy('project:' . $project . ':statistic:code_count:' . $code . ':' . $day, $interval, 1);
        // 状态码耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:code_cost:' . $code . ':' . $day, $interval, $costTime);


        //////
        // 记录项目（应用）统计（按天统计）
        //////
        // 次数
        Redis::hIncrBy('project:' . $project . ':statistic:count', $day, 1);
        // 耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:cost', $day, $costTime);
        // 成功次数
        Redis::hIncrBy('project:' . $project . ':statistic:success_count', $day, $success ? 1 : 0);
        // 失败次数
        Redis::hIncrBy('project:' . $project . ':statistic:error_count', $day, $success ? 0 : 1);

        // 调用次数
        Redis::hIncrBy('project:' . $project . ':statistic:transfer_count:' . $transferTemp, $day, 1);
        // 调用耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:transfer_cost:' . $transferTemp, $day, $costTime);
        // 调用成功次数
        Redis::hIncrBy('project:' . $project . ':statistic:transfer_success:' . $transferTemp, $day, $success ? 1 : 0);
        // 调用失败次数
        Redis::hIncrBy('project:' . $project . ':statistic:transfer_error:' . $transferTemp, $day, $success ? 0 : 1);

        // IP次数
        Redis::hIncrBy('project:' . $project . ':statistic:ip_count:' . $ipTemp, $day, 1);
        // IP耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:ip_cost:' . $ipTemp, $day, $costTime);
        // IP成功次数
        Redis::hIncrBy('project:' . $project . ':statistic:ip_success:' . $ipTemp, $day, $success ? 1 : 0);
        // IP失败次数
        Redis::hIncrBy('project:' . $project . ':statistic:ip_error:' . $ipTemp, $day, $success ? 0 : 1);

        // 状态码次数
        Redis::hIncrBy('project:' . $project . ':statistic:code_count:' . $code, $day, 1);
        // 状态码耗时
        Redis::hIncrByFloat('project:' . $project . ':statistic:code_cost:' . $code, $day, $costTime);

        // 记录项目（应用）IP分布（按天记录）
        Redis::hSetNx('project:' . $project . ':ip:' . $day, $ip, $ip);

        // 记录项目（应用）状态码分布（按天记录）
        Redis::hSetNx('project:' . $project . ':code:' . $day, $code, $code);
        // 记录项目（应用）单状态码链路标识（按天记录）
        Redis::lPush('project:' . $project . ':code_trace:' . $code . ':' . $day, $trace);

        // 记录项目（应用）调用分布（按天记录）
        Redis::hSetNx('project:' . $project . ':transfer:' . $day, $transfer, $transfer);
        // 记录项目（应用）单调用标识（按天记录）
        Redis::lPush('project:' . $project . ':transfer_trace:' . $transferTemp . ':' . $day, $trace);

        // 记录项目（应用）全部调用标识（按天记录）
        Redis::lPush('project:' . $project . ':trace:' . $day, $trace);
    }

    /**
     * 项目（应用）Client统计
     *
     * @author HSK
     * @date 2021-12-13 14:16:44
     *
     * @param array $data
     *
     * @return void
     */
    protected function projectClientStatistics(array $data)
    {
        $trace    = $data['trace'];     // 追踪标识
        $project  = $data['project'];   // 项目（应用）
        $client   = $data['ip'];        // Client（IP）
        $transfer = $data['transfer'];  // 调用
        $costTime = $data['costTime'];  // 消耗时长
        $success  = $data['success'];   // 状态
        $time     = $data['time'];      // 产生时间
        $code     = $data['code'];      // 状态码
        $details  = $data['details'];   // 详情

        // 调用、IP 替换掉“:”，防止redis存储分类层级混乱，兼容IPv6
        $clientTemp   = str_replace(['::', ':'], '@', $client);
        $transferTemp = str_replace(['::', ':'], '@', $transfer);

        // 产生日期
        $day = date('Ymd', $time);

        // 产生时间间隔（五分钟）
        $interval = date('YmdHi', ceil($time / 300) * 300);

        //////
        // 记录项目（应用）统计（按分钟统计，用于图表展示）
        //////
        // 次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:count:' . $day, $interval, 1);
        // 耗时
        Redis::hIncrByFloat('project:' . $project . ':client:' . $clientTemp . ':statistic:cost:' . $day, $interval, $costTime);
        // 成功次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:success_count:' . $day, $interval, $success ? 1 : 0);
        // 失败次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:error_count:' . $day, $interval, $success ? 0 : 1);

        // 调用次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $interval, 1);
        // 调用耗时
        Redis::hIncrByFloat('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $interval, $costTime);
        // 调用成功次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $interval, $success ? 1 : 0);
        // 调用失败次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $interval, $success ? 0 : 1);

        // 状态码次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:code_count:' . $code . ':' . $day, $interval, 1);
        // 状态码耗时
        Redis::hIncrByFloat('project:' . $project . ':client:' . $clientTemp . ':statistic:code_cost:' . $code . ':' . $day, $interval, $costTime);


        //////
        // 记录项目（应用）Client统计（按天统计）
        //////
        // 次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:count', $day, 1);
        // 耗时
        Redis::hIncrByFloat('project:' . $project . ':client:' . $clientTemp . ':statistic:cost', $day, $costTime);
        // 成功次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:success_count', $day, $success ? 1 : 0);
        // 失败次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:error_count', $day, $success ? 0 : 1);

        // 调用次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_count:' . $transferTemp, $day, 1);
        // 调用耗时
        Redis::hIncrByFloat('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_cost:' . $transferTemp, $day, $costTime);
        // 调用链成功次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_success:' . $transferTemp, $day, $success ? 1 : 0);
        // 调用链失败次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:transfer_error:' . $transferTemp, $day, $success ? 0 : 1);

        // 状态码次数
        Redis::hIncrBy('project:' . $project . ':client:' . $clientTemp . ':statistic:code_count:' . $code, $day, 1);
        // 状态码耗时
        Redis::hIncrByFloat('project:' . $project . ':client:' . $clientTemp . ':statistic:code_cost:' . $code, $day, $costTime);

        // 记录项目（应用）Client状态码分布（按天记录）
        Redis::hSetNx('project:' . $project . ':client:' . $clientTemp . ':code:' . $day, $code, $code);
        // 记录项目（应用）Client单状态码链路标识（按天记录）
        Redis::lPush('project:' . $project . ':client:' . $clientTemp . ':code_trace:' . $code . ':' . $day, $trace);

        // 记录项目（应用）Client调用分布（按天记录）
        Redis::hSetNx('project:' . $project . ':client:' . $clientTemp . ':transfer:' . $day, $transfer, $transfer);
        // 记录项目（应用）Client单调用标识（按天记录）
        Redis::lPush('project:' . $project . ':client:' . $clientTemp . ':transfer_trace:' . $transferTemp . ':' . $day, $trace);

        // 记录项目（应用）Client调用标识（按天记录）
        Redis::lPush('project:' . $project . ':client:' . $clientTemp . ':trace:' . $day, $trace);
    }
}
