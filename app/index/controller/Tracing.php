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
        $time     = request()->input('time');
        $details  = request()->input('details');

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

        if (!empty($time)) {
            $where[] = ["time", "like", "$time%"];
        }

        if (!empty($details)) {
            $where[] = ["details", "like", "%$details%"];
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
        $id    = (int)request()->input('id');
        $trace = request()->input('trace');

        if (empty($id) && empty($trace)) {
            return api([], 400, '请求错误');
        }

        $where = [];
        if (!empty($id)) {
            $where[] = ['id', '=', $id];
        } else {
            $where[] = ['trace', '=', $trace];

            if (config('elasticsearch.enable', false)) {
                try {
                    $response = \app\common\service\Elasticsearch::get([
                        'index' => config('elasticsearch.index', 'tracing'),
                        'id'    => $trace,
                    ]);

                    $info = $response['_source'];
                    $info['details'] = json_encode(json_decode($info['details'], true), 448);
                    $info['details'] = str_replace(['\r\n', '\n'], "\n", $info['details']);
                    $info['details'] = htmlspecialchars($info['details']);

                    return api($info);
                } catch (\Throwable $th) {
                }
            }
        }

        $info = Db::name('tracing')->where($where)->find();

        if (empty($info)) {
            return api([], 400, '参数错误');
        }

        $info['details'] = json_encode(json_decode($info['details'], true), 448);
        $info['details'] = str_replace(['\r\n', '\n'], "\n", $info['details']);
        $info['details'] = htmlspecialchars($info['details']);

        return api($info);
    }
}
