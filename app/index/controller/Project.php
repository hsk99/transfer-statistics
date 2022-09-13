<?php

namespace app\index\controller;

use think\facade\Db;

class Project
{
    /**
     * 应用统计
     *
     * @author HSK
     * @date 2022-06-20 22:37:57
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        $project = request()->input('project');
        $date    = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // 统计数据
        $totalStatistic = Db::name('statistic_project')->where('project', $project)->where('day', $day)->find();
        if (!empty($totalStatistic)) {
            $totalStatistic['average_cost'] = empty($totalStatistic['count']) ? 0 : round($totalStatistic['cost'] / $totalStatistic['count'] * 1000, 2);
        } else {
            $totalStatistic['count'] = $totalStatistic['success_count'] = $totalStatistic['error_count'] = $totalStatistic['average_cost'] = 0;
        }


        // 统计图表数据
        $intervalData = Db::name('statistic_project_interval')
            ->where('project', $project)
            ->where('day', $day)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });
        $intervalData = !empty($intervalData) ? $intervalData->toArray() : [];
        $intervalData = array_column($intervalData, null, 'time');
        $chartList     = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 60) * 60 - $time) / 60;
        $intervalCount = $intervalCount > 1440 ? 1440 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $interval = date('YmdHi', $time + $i * 60);
            if (empty($intervalData[$interval])) {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = 0;
                $chartList['success_count'][$interval] = 0;
                $chartList['error_count'][$interval]   = 0;
                $chartList['average_cost'][$interval]  = 0;
            } else {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = $intervalData[$interval]['count'];
                $chartList['success_count'][$interval] = $intervalData[$interval]['success_count'];
                $chartList['error_count'][$interval]   = $intervalData[$interval]['error_count'];
                $chartList['average_cost'][$interval]  = $intervalData[$interval]['average_cost'];
            }
        }
        $chartList['time']          = array_values($chartList['time'] ?? []);
        $chartList['count']         = array_values($chartList['count'] ?? []);
        $chartList['success_count'] = array_values($chartList['success_count'] ?? []);
        $chartList['error_count']   = array_values($chartList['error_count'] ?? []);
        $chartList['average_cost']  = array_values($chartList['average_cost'] ?? []);


        // IP统计数据
        $ipStatistic = Db::name('statistic_project_ip')
            ->where('project', $project)
            ->where('day', $day)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });


        // 调用统计数据
        $transferStatistic = Db::name('statistic_project_transfer')
            ->where('project', $project)
            ->where('day', $day)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });


        // 状态码统计数据
        $codeStatistic = Db::name('statistic_project_code')
            ->where('project', $project)
            ->where('day', $day)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });

        return view('project/index', [
            'project'           => $project,
            'date'              => $date,
            'totalStatistic'    => $totalStatistic,
            'chartList'         => json_encode($chartList, 320),
            'ipStatistic'       => $ipStatistic,
            'transferStatistic' => $transferStatistic,
            'codeStatistic'     => $codeStatistic,
        ]);
    }

    /**
     * IP统计
     *
     * @author HSK
     * @date 2022-09-13 14:47:59
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function ipStatistic(\support\Request $request)
    {
        $page    = (int)request()->input('page', 1);
        $limit   = (int)request()->input('limit', 10);
        $project = request()->input('project');
        $date    = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // IP统计数据
        $list = Db::name('statistic_project_ip')
            ->where('project', $project)
            ->where('day', $day)
            ->order('count', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });

        return api($list);
    }

    /**
     * IP
     *
     * @author HSK
     * @date 2022-06-21 14:14:38
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function ip(\support\Request $request)
    {
        $project = request()->input('project');
        $ip      = request()->input('ip');
        $date    = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // 统计图表数据
        $intervalData = Db::name('statistic_project_ip_interval')
            ->where('project', $project)
            ->where('day', $day)
            ->where('ip', $ip)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });
        $intervalData = !empty($intervalData) ? $intervalData->toArray() : [];
        $intervalData = array_column($intervalData, null, 'time');
        $chartList     = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 60) * 60 - $time) / 60;
        $intervalCount = $intervalCount > 1440 ? 1440 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $interval = date('YmdHi', $time + $i * 60);
            if (empty($intervalData[$interval])) {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = 0;
                $chartList['success_count'][$interval] = 0;
                $chartList['error_count'][$interval]   = 0;
                $chartList['average_cost'][$interval]  = 0;
            } else {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = $intervalData[$interval]['count'];
                $chartList['success_count'][$interval] = $intervalData[$interval]['success_count'];
                $chartList['error_count'][$interval]   = $intervalData[$interval]['error_count'];
                $chartList['average_cost'][$interval]  = $intervalData[$interval]['average_cost'];
            }
        }
        $chartList['time']          = array_values($chartList['time'] ?? []);
        $chartList['count']         = array_values($chartList['count'] ?? []);
        $chartList['success_count'] = array_values($chartList['success_count'] ?? []);
        $chartList['error_count']   = array_values($chartList['error_count'] ?? []);
        $chartList['average_cost']  = array_values($chartList['average_cost'] ?? []);

        return view('project/ip', [
            'date'      => $date,
            'project'   => $project,
            'ip'        => $ip,
            'chartList' => json_encode($chartList, 320),
        ]);
    }

    /**
     * 调用统计
     *
     * @author HSK
     * @date 2022-09-13 15:04:02
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function transferStatistic(\support\Request $request)
    {
        $page    = (int)request()->input('page', 1);
        $limit   = (int)request()->input('limit', 10);
        $project = request()->input('project');
        $date    = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // 调用统计数据
        $list = Db::name('statistic_project_transfer')
            ->where('project', $project)
            ->where('day', $day)
            ->order('count', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });

        return api($list);
    }

    /**
     * 调用
     *
     * @author HSK
     * @date 2022-06-21 14:35:01
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function transfer(\support\Request $request)
    {
        $project  = request()->input('project');
        $transfer = request()->input('transfer');
        $date     = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // 统计图表数据
        $intervalData = Db::name('statistic_project_transfer_interval')
            ->where('project', $project)
            ->where('day', $day)
            ->where('transfer', $transfer)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });
        $intervalData = !empty($intervalData) ? $intervalData->toArray() : [];
        $intervalData = array_column($intervalData, null, 'time');
        $chartList     = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 60) * 60 - $time) / 60;
        $intervalCount = $intervalCount > 1440 ? 1440 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $interval = date('YmdHi', $time + $i * 60);
            if (empty($intervalData[$interval])) {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = 0;
                $chartList['success_count'][$interval] = 0;
                $chartList['error_count'][$interval]   = 0;
                $chartList['average_cost'][$interval]  = 0;
            } else {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = $intervalData[$interval]['count'];
                $chartList['success_count'][$interval] = $intervalData[$interval]['success_count'];
                $chartList['error_count'][$interval]   = $intervalData[$interval]['error_count'];
                $chartList['average_cost'][$interval]  = $intervalData[$interval]['average_cost'];
            }
        }
        $chartList['time']          = array_values($chartList['time'] ?? []);
        $chartList['count']         = array_values($chartList['count'] ?? []);
        $chartList['success_count'] = array_values($chartList['success_count'] ?? []);
        $chartList['error_count']   = array_values($chartList['error_count'] ?? []);
        $chartList['average_cost']  = array_values($chartList['average_cost'] ?? []);

        return view('project/transfer', [
            'date'      => $date,
            'project'   => $project,
            'transfer'  => $transfer,
            'chartList' => json_encode($chartList, 320),
        ]);
    }

    /**
     * IP统计
     *
     * @author HSK
     * @date 2022-09-13 15:09:54
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function codeStatistic(\support\Request $request)
    {
        $page    = (int)request()->input('page', 1);
        $limit   = (int)request()->input('limit', 10);
        $project = request()->input('project');
        $date    = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // 状态码统计数据
        $list = Db::name('statistic_project_code')
            ->where('project', $project)
            ->where('day', $day)
            ->order('count', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });

        return api($list);
    }

    /**
     * 状态码
     *
     * @author HSK
     * @date 2022-06-21 14:43:00
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function code(\support\Request $request)
    {
        $project = request()->input('project');
        $code    = request()->input('code');
        $date    = request()->input('date', date('Y-m-d', time()));

        // 查看的日期
        $day = (int)date('Ymd', strtotime($date));

        // 统计图表数据
        $intervalData = Db::name('statistic_project_code_interval')
            ->where('project', $project)
            ->where('day', $day)
            ->where('code', $code)
            ->select()
            ->each(function ($item, $key) {
                $item['average_cost'] = (0 == $item['count']) ? 0 : round($item['cost'] / $item['count'] * 1000, 2);
                return $item;
            });
        $intervalData = !empty($intervalData) ? $intervalData->toArray() : [];
        $intervalData = array_column($intervalData, null, 'time');
        $chartList     = [];
        $time          = strtotime($day);
        $intervalCount = (int)(ceil(time() / 60) * 60 - $time) / 60;
        $intervalCount = $intervalCount > 1440 ? 1440 : $intervalCount;
        for ($i = 0; $i < $intervalCount; $i++) {
            $interval = date('YmdHi', $time + $i * 60);
            if (empty($intervalData[$interval])) {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = 0;
                $chartList['average_cost'][$interval]  = 0;
            } else {
                $chartList['time'][$interval]          = date('Y-m-d H:i', $time + $i * 60);
                $chartList['count'][$interval]         = $intervalData[$interval]['count'];
                $chartList['average_cost'][$interval]  = $intervalData[$interval]['average_cost'];
            }
        }
        $chartList['time']          = array_values($chartList['time'] ?? []);
        $chartList['count']         = array_values($chartList['count'] ?? []);
        $chartList['average_cost']  = array_values($chartList['average_cost'] ?? []);

        return view('project/code', [
            'date'      => $date,
            'project'   => $project,
            'code'      => $code,
            'chartList' => json_encode($chartList, 320),
        ]);
    }
}
