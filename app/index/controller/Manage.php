<?php

namespace app\index\controller;

use think\facade\Db;
use support\Redis;
use app\common\service\Elasticsearch;

class Manage
{
    /**
     * 应用管理
     *
     * @author HSK
     * @date 2022-07-18 23:39:13
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        return view('manage/index');
    }

    /**
     * 应用列表
     *
     * @author HSK
     * @date 2022-07-19 16:09:42
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function list(\support\Request $request)
    {
        $list = Db::name('statistic_project')
            ->field('project, SUM(count) AS total, SUM(success_count) AS success, SUM(error_count) AS error')
            ->group('project')
            ->order('total', 'desc')
            ->select();

        return api([
            'total' => count($list),
            'list' => $list
        ]);
    }

    /**
     * 删除
     *
     * @author HSK
     * @date 2022-07-19 16:42:53
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function remove(\support\Request $request)
    {
        try {
            $project = request()->input('project');

            if (empty($project)) {
                return api([], 400, '参数错误');
            }

            // 清除 MySql
            try {
                Db::startTrans();

                Db::name('project')->where('project', $project)->delete();
                Db::name('statistic_project')->where('project', $project)->delete();
                Db::name('statistic_project_code')->where('project', $project)->delete();
                Db::name('statistic_project_code_interval')->where('project', $project)->delete();
                Db::name('statistic_project_interval')->where('project', $project)->delete();
                Db::name('statistic_project_ip')->where('project', $project)->delete();
                Db::name('statistic_project_ip_code')->where('project', $project)->delete();
                Db::name('statistic_project_ip_code_interval')->where('project', $project)->delete();
                Db::name('statistic_project_ip_interval')->where('project', $project)->delete();
                Db::name('statistic_project_ip_transfer')->where('project', $project)->delete();
                Db::name('statistic_project_ip_transfer_interval')->where('project', $project)->delete();
                Db::name('statistic_project_transfer')->where('project', $project)->delete();
                Db::name('statistic_project_transfer_interval')->where('project', $project)->delete();
                Db::name('tracing')->where('project', $project)->delete();

                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                \Hsk99\WebmanException\RunException::report($th);
                return api([], 400, '操作失败');
            }

            // 清除 Redis
            try {
                Redis::hDel('TransferStatistics:project', $project);
                array_map(function ($key) {
                    $key = str_replace(config('redis.default.prefix'), '', $key);
                    Redis::del($key);
                }, Redis::keys("TransferStatistics:project:$project:*"));
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }

            // 清除 全局统计
            if (0 === Db::name('project')->count()) {
                try {
                    Db::name('statistic')->delete(true);
                    Redis::del('TransferStatistics:statistic:cost');
                    Redis::del('TransferStatistics:statistic:count');
                    Redis::del('TransferStatistics:statistic:success_count');
                    Redis::del('TransferStatistics:statistic:error_count');
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }
            }

            // 清除 Elasticsearch
            try {
                if (config('elasticsearch.enable', false)) {
                    Elasticsearch::deleteByQuery([
                        'index'       => config('elasticsearch.index', 'tracing'),
                        'conflicts'   => 'proceed',
                        'scroll_size' => 5000,
                        'body'        => [
                            'query' => [
                                'match' => [
                                    'project' => $project
                                ]
                            ]
                        ]
                    ]);
                }
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }

            return api([], 200, '操作成功');
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
            return api([], 400, '操作失败');
        }
    }
}
