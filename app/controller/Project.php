<?php

namespace app\controller;

use support\Redis;

/**
 * 项目（应用）
 *
 * @author HSK
 * @date 2021-12-16 15:19:09
 */
class Project
{
    /**
     * 首页
     *
     * @author HSK
     * @date 2021-12-16 15:20:51
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        //////
        // 项目（应用）总统计
        //////
        // 总请求数
        $totalStatistic['count'] = Redis::hGet('project:' . $project . ':statistic:count', $day) ?: 0;
        // 总成功数
        $totalStatistic['successCount'] = Redis::hGet('project:' . $project . ':statistic:success_count', $day) ?: 0;
        // 总失败数
        $totalStatistic['errorCount'] = Redis::hGet('project:' . $project . ':statistic:error_count', $day) ?: 0;
        // 总耗时
        $totalStatistic['cost'] = Redis::hGet('project:' . $project . ':statistic:cost', $day) ?: 0;
        // 平均耗时
        $totalStatistic['averageCost'] = (0 == $totalStatistic['count']) ? 0 : round($totalStatistic['cost'] / $totalStatistic['count'] * 1000, 2);


        //////
        // 获取项目（应用）调用统计数据（当天时间五分钟一统计）
        //////
        $chartCount        = Redis::hGetAll('project:' . $project . ':statistic:count:' . $day) ?: [];
        $chartCost         = Redis::hGetAll('project:' . $project . ':statistic:cost:' . $day) ?: [];
        $chartSuccessCount = Redis::hGetAll('project:' . $project . ':statistic:success_count:' . $day) ?: [];
        $chartErrorCount   = Redis::hGetAll('project:' . $project . ':statistic:error_count:' . $day) ?: [];
        // 获取间隔
        $intervalList  = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 300) * 300 - $time) / 300;
        $intervalCount = $intervalCount > 288 ? 288 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $intervalList[] = date('YmdHi', $time + $i * 300);
        }
        $intervalList = array_merge($intervalList, array_keys($chartCount));
        sort($intervalList);
        // 组装数据
        $chartList = [];
        array_map(function ($interval) use (&$chartList, &$chartCount, &$chartCost, &$chartSuccessCount, &$chartErrorCount) {
            $chartList['time'][$interval]         = date('y-m-d H:i', strtotime($interval));
            $chartList['count'][$interval]        = $chartCount[$interval] ?? 0;
            $chartList['cost'][$interval]         = $chartCost[$interval] ?? 0;
            $chartList['successCount'][$interval] = $chartSuccessCount[$interval] ?? 0;
            $chartList['errorCount'][$interval]   = $chartErrorCount[$interval] ?? 0;
            $chartList['averageCost'][$interval]  = (0 == $chartList['count'][$interval]) ? 0 : round($chartList['cost'][$interval] / $chartList['count'][$interval] * 1000, 2);
        }, $intervalList);
        $chartList['time']         = array_values($chartList['time'] ?? []);
        $chartList['count']        = array_values($chartList['count'] ?? []);
        $chartList['cost']         = array_values($chartList['cost'] ?? []);
        $chartList['successCount'] = array_values($chartList['successCount'] ?? []);
        $chartList['errorCount']   = array_values($chartList['errorCount'] ?? []);
        $chartList['averageCost']  = array_values($chartList['averageCost'] ?? []);


        ///////
        // 获取调用入口
        //////
        $projectTransfer = Redis::hGetAll('project:' . $project . ':transfer:' . $day) ?: [];
        $projectTransfer = array_values($projectTransfer);


        //////
        // 获取调用IP
        //////
        $projectIp = Redis::hGetAll('project:' . $project . ':ip:' . $day) ?: [];
        $projectIp = array_values($projectIp);


        //////
        // 获取状态码
        //////
        $projectCode = Redis::hGetAll('project:' . $project . ':code:' . $day) ?: [];
        $projectCode = array_values($projectCode);

        return view('project/index', [
            'date'            => $date,
            'project'         => $project,
            'totalStatistic'  => $totalStatistic,
            'chartList'       => json_encode($chartList, 320),
            'projectTransfer' => $projectTransfer,
            'projectIp'       => $projectIp,
            'projectCode'     => $projectCode,
        ]);
    }

    /**
     * 调用记录
     *
     * @author HSK
     * @date 2021-12-22 16:12:31
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function tracingList(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));
        $page    = (int)$request->input('page', 1);
        $limit   = (int)$request->input('limit', 10);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        // 计算读取范围
        $start = (1 === $page) ? 0 : ($page - 1) * $limit;
        $end   = $page * $limit - 1;

        $trace       = Redis::lRange('project:' . $project . ':trace:' . $day, $start, $end);
        $total       = Redis::lLen('project:' . $project . ':trace:' . $day);
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

    /**
     * 调用入口
     *
     * @author HSK
     * @date 2021-12-21 17:11:40
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function transfer(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $transfer = (string)$request->input('transfer', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));

        $transferTemp = str_replace(['::', ':'], '@', $transfer);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        //////
        // 获取项目（应用）调用入口统计数据（当天时间五分钟一统计）
        //////
        $chartCount        = Redis::hGetAll('project:' . $project . ':statistic:transfer_count:' . $transferTemp . ':' . $day) ?: [];
        $chartCost         = Redis::hGetAll('project:' . $project . ':statistic:transfer_cost:' . $transferTemp . ':' . $day) ?: [];
        $chartSuccessCount = Redis::hGetAll('project:' . $project . ':statistic:transfer_success:' . $transferTemp . ':' . $day) ?: [];
        $chartErrorCount   = Redis::hGetAll('project:' . $project . ':statistic:transfer_error:' . $transferTemp . ':' . $day) ?: [];
        // 获取间隔
        $intervalList  = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 300) * 300 - $time) / 300;
        $intervalCount = $intervalCount > 288 ? 288 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $intervalList[] = date('YmdHi', $time + $i * 300);
        }
        $intervalList = array_merge($intervalList, array_keys($chartCount));
        sort($intervalList);
        // 组装数据
        $chartList = [];
        array_map(function ($interval) use (&$chartList, &$chartCount, &$chartCost, &$chartSuccessCount, &$chartErrorCount) {
            $chartList['time'][$interval]         = date('y-m-d H:i', strtotime($interval));
            $chartList['count'][$interval]        = $chartCount[$interval] ?? 0;
            $chartList['cost'][$interval]         = $chartCost[$interval] ?? 0;
            $chartList['successCount'][$interval] = $chartSuccessCount[$interval] ?? 0;
            $chartList['errorCount'][$interval]   = $chartErrorCount[$interval] ?? 0;
            $chartList['averageCost'][$interval]  = (0 == $chartList['count'][$interval]) ? 0 : round($chartList['cost'][$interval] / $chartList['count'][$interval] * 1000, 2);
        }, $intervalList);
        $chartList['time']         = array_values($chartList['time'] ?? []);
        $chartList['count']        = array_values($chartList['count'] ?? []);
        $chartList['cost']         = array_values($chartList['cost'] ?? []);
        $chartList['successCount'] = array_values($chartList['successCount'] ?? []);
        $chartList['errorCount']   = array_values($chartList['errorCount'] ?? []);
        $chartList['averageCost']  = array_values($chartList['averageCost'] ?? []);

        return view('project/transfer', [
            'date'      => $date,
            'project'   => $project,
            'transfer'  => $transfer,
            'chartList' => json_encode($chartList, 320),
        ]);
    }

    /**
     * 入口调用记录
     *
     * @author HSK
     * @date 2021-12-22 13:43:36
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function transferTracingList(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $transfer = (string)$request->input('transfer', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));
        $page    = (int)$request->input('page', 1);
        $limit   = (int)$request->input('limit', 10);

        $transferTemp = str_replace(['::', ':'], '@', $transfer);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        // 计算读取范围
        $start = (1 === $page) ? 0 : ($page - 1) * $limit;
        $end   = $page * $limit - 1;

        $trace       = Redis::lRange('project:' . $project . ':transfer_trace:' . $transferTemp . ':' . $day, $start, $end);
        $total       = Redis::lLen('project:' . $project . ':transfer_trace:' . $transferTemp . ':' . $day);
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

    /**
     * 调用IP
     *
     * @author HSK
     * @date 2021-12-22 14:13:17
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function ip(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $ip      = (string)$request->input('ip', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));

        $ipTemp = str_replace(['::', ':'], '@', $ip);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        //////
        // 获取项目（应用）调用IP统计数据（当天时间五分钟一统计）
        //////
        $chartCount        = Redis::hGetAll('project:' . $project . ':client:' . $ipTemp . ':statistic:count:' . $day) ?: [];
        $chartCost         = Redis::hGetAll('project:' . $project . ':client:' . $ipTemp . ':statistic:cost:' . $day) ?: [];
        $chartSuccessCount = Redis::hGetAll('project:' . $project . ':client:' . $ipTemp . ':statistic:success_count:' . $day) ?: [];
        $chartErrorCount   = Redis::hGetAll('project:' . $project . ':client:' . $ipTemp . ':statistic:error_count:' . $day) ?: [];

        // 获取间隔
        $intervalList  = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 300) * 300 - $time) / 300;
        $intervalCount = $intervalCount > 288 ? 288 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $intervalList[] = date('YmdHi', $time + $i * 300);
        }
        $intervalList = array_merge($intervalList, array_keys($chartCount));
        sort($intervalList);
        // 组装数据
        $chartList = [];
        array_map(function ($interval) use (&$chartList, &$chartCount, &$chartCost, &$chartSuccessCount, &$chartErrorCount) {
            $chartList['time'][$interval]         = date('y-m-d H:i', strtotime($interval));
            $chartList['count'][$interval]        = $chartCount[$interval] ?? 0;
            $chartList['cost'][$interval]         = $chartCost[$interval] ?? 0;
            $chartList['successCount'][$interval] = $chartSuccessCount[$interval] ?? 0;
            $chartList['errorCount'][$interval]   = $chartErrorCount[$interval] ?? 0;
            $chartList['averageCost'][$interval]  = (0 == $chartList['count'][$interval]) ? 0 : round($chartList['cost'][$interval] / $chartList['count'][$interval] * 1000, 2);
        }, $intervalList);
        $chartList['time']         = array_values($chartList['time'] ?? []);
        $chartList['count']        = array_values($chartList['count'] ?? []);
        $chartList['cost']         = array_values($chartList['cost'] ?? []);
        $chartList['successCount'] = array_values($chartList['successCount'] ?? []);
        $chartList['errorCount']   = array_values($chartList['errorCount'] ?? []);
        $chartList['averageCost']  = array_values($chartList['averageCost'] ?? []);

        return view('project/ip', [
            'date'      => $date,
            'project'   => $project,
            'ip'        => $ip,
            'chartList' => json_encode($chartList, 320),
        ]);
    }

    /**
     * IP调用记录
     *
     * @author HSK
     * @date 2021-12-22 14:25:51
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function ipTracingList(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $ip      = (string)$request->input('ip', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));
        $page    = (int)$request->input('page', 1);
        $limit   = (int)$request->input('limit', 10);

        $ipTemp = str_replace(['::', ':'], '@', $ip);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        // 计算读取范围
        $start = (1 === $page) ? 0 : ($page - 1) * $limit;
        $end   = $page * $limit - 1;

        $trace       = Redis::lRange('project:' . $project . ':client:' . $ipTemp . ':trace:' . $day, $start, $end);
        $total       = Redis::lLen('project:' . $project . ':client:' . $ipTemp . ':trace:' . $day);
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

    /**
     * 状态码
     *
     * @author HSK
     * @date 2021-12-22 23:21:25
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function code(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $code    = $request->input('code', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        //////
        // 获取项目（应用）状态码统计数据（当天时间五分钟一统计）
        //////
        $chartCount = Redis::hGetAll('project:' . $project . ':statistic:code_count:' . $code . ':' . $day) ?: [];
        $chartCost  = Redis::hGetAll('project:' . $project . ':statistic:code_cost:' . $code . ':' . $day) ?: [];

        // 获取间隔
        $intervalList  = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 300) * 300 - $time) / 300;
        $intervalCount = $intervalCount > 288 ? 288 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $intervalList[] = date('YmdHi', $time + $i * 300);
        }
        $intervalList = array_merge($intervalList, array_keys($chartCount));
        sort($intervalList);
        // 组装数据
        $chartList = [];
        array_map(function ($interval) use (&$chartList, &$chartCount, &$chartCost, &$chartSuccessCount, &$chartErrorCount) {
            $chartList['time'][$interval]        = date('y-m-d H:i', strtotime($interval));
            $chartList['count'][$interval]       = $chartCount[$interval] ?? 0;
            $chartList['cost'][$interval]        = $chartCost[$interval] ?? 0;
            $chartList['averageCost'][$interval] = (0 == $chartList['count'][$interval]) ? 0 : round($chartList['cost'][$interval] / $chartList['count'][$interval] * 1000, 2);
        }, $intervalList);
        $chartList['time']        = array_values($chartList['time'] ?? []);
        $chartList['count']       = array_values($chartList['count'] ?? []);
        $chartList['cost']        = array_values($chartList['cost'] ?? []);
        $chartList['averageCost'] = array_values($chartList['averageCost'] ?? []);

        return view('project/code', [
            'date'      => $date,
            'project'   => $project,
            'code'      => $code,
            'chartList' => json_encode($chartList, 320),
        ]);
    }

    /**
     * 状态码调用记录
     *
     * @author HSK
     * @date 2021-12-22 23:43:06
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function codeTracingList(\support\Request $request)
    {
        $project = (string)$request->input('project', '');
        $code    = $request->input('code', '');
        $date    = (string)$request->input('date', date('Y-m-d', time()));
        $page    = (int)$request->input('page', 1);
        $limit   = (int)$request->input('limit', 10);

        // 查看的日期
        $day = date('Ymd', strtotime($date));

        // 计算读取范围
        $start = (1 === $page) ? 0 : ($page - 1) * $limit;
        $end   = $page * $limit - 1;

        $trace       = Redis::lRange('project:' . $project . ':code_trace:' . $code . ':' . $day, $start, $end);
        $total       = Redis::lLen('project:' . $project . ':code_trace:' . $code . ':' . $day);
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
