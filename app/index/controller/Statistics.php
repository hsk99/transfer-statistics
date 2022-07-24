<?php

namespace app\index\controller;

use think\facade\Db;

class Statistics
{
    /**
     * 统计
     *
     * @author HSK
     * @date 2022-06-19 16:08:43
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        $statisticData = Db::name('statistic')
            ->field('count, cost, success_count, error_count')
            ->where('day', date('Ymd'))
            ->find();
        $statisticData['project_count'] = Db::name('project')->count();

        $projectStatisticData = Db::name('statistic_project')
            ->field('project, SUM(count) AS total, SUM(success_count) AS success, SUM(error_count) AS error, SUM(cost) AS cost')
            ->group('project')
            ->order('total', 'asc')
            ->select()
            ->toArray();
        $projectStatisticChart['x']       = array_column($projectStatisticData, 'project');
        $projectStatisticChart['total']   = array_column($projectStatisticData, 'total');
        $projectStatisticChart['success'] = array_column($projectStatisticData, 'success');
        $projectStatisticChart['error']   = array_column($projectStatisticData, 'error');

        $projectIpStatisticList = Db::name('statistic_project_ip')
            ->field('ip, SUM(count) AS total, SUM(success_count) AS success, SUM(error_count) AS error, SUM(cost) AS cost')
            ->group('ip')
            ->order('total', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        $projectTransferStatisticList = Db::name('statistic_project_transfer')
            ->field('transfer, SUM(count) AS total, SUM(success_count) AS success, SUM(error_count) AS error, SUM(cost) AS cost')
            ->group('transfer')
            ->order('total', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        return view('statistics/index', [
            'statisticData'                => $statisticData,
            'projectStatisticChart'        => json_encode($projectStatisticChart, 320),
            'projectIpStatisticList'       => $projectIpStatisticList,
            'projectTransferStatisticList' => $projectTransferStatisticList,
        ]);
    }
}
