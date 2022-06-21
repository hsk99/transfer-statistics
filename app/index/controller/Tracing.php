<?php

namespace app\index\controller;

use think\facade\Db;

class Tracing
{
    /**
     * 调用记录列表
     *
     * @author HSK
     * @date 2022-06-20 17:05:01
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function list(\support\Request $request)
    {
        $page     = (int)request()->input('page', 1);
        $limit    = (int)request()->input('limit', 10);
        $date     = request()->input('date');
        $project  = request()->input('project');
        $ip       = request()->input('ip');
        $transfer = request()->input('transfer');
        $code     = request()->input('code');

        $where = [];

        if (!empty($date)) {
            $day = (int)date('Ymd', strtotime($date));
            $where[] = ["day", "=", $day];
        }

        if (!empty($project)) {
            $where[] = ["project", "=", $project];
        }

        if (!empty($ip)) {
            $where[] = ["ip", "=", $ip];
        }

        if (!empty($transfer)) {
            $where[] = ["transfer", "=", $transfer];
        }

        if (!empty($code)) {
            $where[] = ["code", "=", $code];
        }

        $list = Db::name('tracing')
            ->field('id, time, project, ip, transfer, cost_time, success')
            ->where($where)
            ->order('time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->each(function ($item, $key) {
                $item['cost_time'] = round($item['cost_time'] * 1000, 2);
                return $item;
            });

        return api($list);
    }

    /**
     * 调用记录详情
     *
     * @author HSK
     * @date 2022-06-20 17:26:11
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function info(\support\Request $request)
    {
        $id = (int)request()->input('id');

        if (empty($id)) {
            return api([], 400, '请求错误');
        }

        $info = Db::name('tracing')->find($id);

        if (empty($info)) {
            return api([], 400, '参数错误');
        }

        $info['details'] = json_encode(json_decode($info['details'], true), 448);

        return api($info);
    }
}
