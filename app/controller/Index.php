<?php

namespace app\controller;

use support\Redis;

/**
 * 首页
 *
 * @author HSK
 * @date 2021-12-16 13:37:31
 */
class Index
{
    /**
     * 首页
     *
     * @author HSK
     * @date 2021-12-16 13:37:40
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        $date = (string)$request->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        //////
        // 总统计
        //////
        // 总请求数
        $totalStatistic['count'] = Redis::hGet('statistic:count', $day) ?: 0;
        // 总成功数
        $totalStatistic['successCount'] = Redis::hGet('statistic:success_count', $day) ?: 0;
        // 总失败数
        $totalStatistic['errorCount'] = Redis::hGet('statistic:error_count', $day) ?: 0;
        // 总耗时
        $totalStatistic['cost'] = Redis::hGet('statistic:cost', $day) ?: 0;
        // 平均耗时
        $totalStatistic['averageCost'] = (0 == $totalStatistic['count']) ? 0 : round($totalStatistic['cost'] / $totalStatistic['count'] * 1000, 2);


        // 获取项目（应用）
        $project = Redis::hGetAll('project') ?: [];


        //////
        // 获取项目（应用）整体统计
        //////
        $projectStatistic = array_map(function ($item) use (&$day) {
            $hashKey = 'project:' . $item . ':statistic:';

            // 应用
            $temp['project'] = $item;
            // 总请求数
            $temp['count'] = Redis::hGet($hashKey . 'count', $day) ?: 0;
            // 总成功数
            $temp['successCount'] = Redis::hGet($hashKey . 'success_count', $day) ?: 0;
            // 总失败数
            $temp['errorCount'] = Redis::hGet($hashKey . 'error_count', $day) ?: 0;
            // 总耗时
            $temp['cost'] = Redis::hGet($hashKey . 'cost', $day) ?: 0;
            // 平均耗时
            $temp['averageCost'] = (0 == $temp['count']) ? 0 : round($temp['cost'] / $temp['count'] * 1000, 2);

            return $temp;
        }, $project);


        //////
        // 获取调用链路统计
        //////
        $transferStatistic = [];
        array_map(function ($item) use (&$day, &$transferStatistic) {
            // 获取项目（应用）调用链路
            $projectTransfer = Redis::hGetAll('project:' . $item . ':transfer:' . $day) ?: [];

            array_map(function ($transfer) use (&$item, &$day, &$transferStatistic) {
                $transferTemp = str_replace(['::', ':'], '@', $transfer);

                // 调用链路次数
                $count = Redis::hGet('project:' . $item . ':statistic:transfer_count:' . $transferTemp, $day);
                // 调用链路耗时
                $cost = Redis::hGet('project:' . $item . ':statistic:transfer_cost:' . $transferTemp, $day);
                // 调用链路成功次数
                $success = Redis::hGet('project:' . $item . ':statistic:transfer_success:' . $transferTemp, $day);
                // 调用链路失败次数
                $error = Redis::hGet('project:' . $item . ':statistic:transfer_error:' . $transferTemp, $day);
                // 平均耗时
                $averageCost = (0 == $count) ? 0 : round($cost / $count * 1000, 2);

                $transferStatistic[] = [
                    'project'     => $item,
                    'transfer'    => $transfer,
                    'count'       => $count,
                    'cost'        => $cost,
                    'success'     => $success,
                    'error'       => $error,
                    'averageCost' => $averageCost,
                ];
            }, $projectTransfer);
        }, $project);


        //////
        // 获取IP统计
        //////
        $ipStatistic = [];
        array_map(function ($item) use (&$day, &$ipStatistic) {
            // 获取项目（应用）调用IP
            $projectIp = Redis::hGetAll('project:' . $item . ':ip:' . $day) ?: [];

            array_map(function ($ip) use (&$item, &$day, &$ipStatistic) {
                $ipTemp = str_replace(['::', ':'], '@', $ip);

                // 调用IP次数
                $count = Redis::hGet('project:' . $item . ':statistic:ip_count:' . $ipTemp, $day);
                // 调用IP耗时
                $cost = Redis::hGet('project:' . $item . ':statistic:ip_cost:' . $ipTemp, $day);
                // 调用IP成功次数
                $success = Redis::hGet('project:' . $item . ':statistic:ip_success:' . $ipTemp, $day);
                // 调用IP失败次数
                $error = Redis::hGet('project:' . $item . ':statistic:ip_error:' . $ipTemp, $day);
                // 平均耗时
                $averageCost = (0 == $count) ? 0 : round($cost / $count * 1000, 2);

                $ipStatistic[] = [
                    'project'     => $item,
                    'ip'          => $ip,
                    'count'       => $count,
                    'cost'        => $cost,
                    'success'     => $success,
                    'error'       => $error,
                    'averageCost' => $averageCost,
                ];
            }, $projectIp);
        }, $project);

        return view('index/index', [
            'date'              => $date,
            'totalStatistic'    => $totalStatistic,
            'projectStatistic'  => $projectStatistic,
            'transferStatistic' => $transferStatistic,
            'ipStatistic'       => $ipStatistic,
        ]);
    }

    /**
     * 调用记录
     *
     * @author HSK
     * @date 2021-12-22 16:22:47
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function tracingList(\support\Request $request)
    {
        $date  = (string)$request->input('date', date('Y-m-d', time()));
        $page  = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        // 计算读取范围
        $start = (1 === $page) ? 0 : ($page - 1) * $limit;
        $end   = $page * $limit - 1;

        $trace       = Redis::lRange('trace:' . $day, $start, $end);
        $total       = Redis::lLen('trace:' . $day);
        $tracingList = [];
        if (!empty($trace)) {
            // 获取链路详细信息
            $tracingList = Redis::hMGet('tracing:' . $day, $trace);

            // 处理数据
            $tracingList = array_map(function ($item) {
                $item = json_decode($item, true);

                $item['details']  = json_encode(json_decode($item['details'], 320), 448);
                $item['costTime'] = round($item['costTime'] * 1000, 2);
                $item['time']     = date('Y-m-d H:i:s', $item['time']);

                return $item;
            }, $tracingList);
        }

        return api([
            'total'       => $total,
            'tracingList' => $tracingList,
        ]);
    }
}
