<?php

namespace app\common\service;

use think\facade\Db;
use app\common\service\Elasticsearch;

class SyncIndexToElasticSearch
{
    /**
     * 执行同步
     *
     * @author HSK
     * @date 2022-08-31 09:24:15
     *
     * @param integer $workerId
     * @param integer $workerCount
     * 
     * @return void
     */
    public static function run(int $workerId, int $workerCount)
    {
        if (!config('elasticsearch.enable', false)) {
            return;
        }

        try {
            if (!Elasticsearch::indicesExists(config('elasticsearch.index', 'tracing'))) {
                Elasticsearch::indicesCreate(config('elasticsearch.index', 'tracing'), [
                    'body' => [
                        'settings' => [
                            'max_result_window' => 1000000
                        ]
                    ]
                ]);
            }

            $tracingDbCount = Db::name('tracing')->count();
            $tracingEsCount = Elasticsearch::count(config('elasticsearch.index', 'tracing'))['count'];

            if ($tracingDbCount <= $tracingEsCount) {
                return;
            }

            $workerCount       = ($workerCount > 1) ? $workerCount - 1 : 1;
            $workerHandleCount = ceil($tracingDbCount / $workerCount);

            if (0 === $workerId) {
                $point    = static::binaryStartPoint(0, $tracingDbCount);
                $endPoint = $tracingDbCount;
            } else {
                $endPoint = $workerId * $workerHandleCount;
                $point    = static::binaryStartPoint(($workerId - 1) * $workerHandleCount, $endPoint);
            }

            switch (true) {
                case $endPoint - $point <= 5:
                    $limit = 5;
                    break;
                case $endPoint - $point <= 10:
                    $limit = 10;
                    break;
                case $endPoint - $point <= 50:
                    $limit = 50;
                    break;
                case $endPoint - $point <= 100:
                    $limit = 100;
                    break;
                case $endPoint - $point <= 500:
                    $limit = 500;
                    break;
                default:
                    $limit = 1000;
                    break;
            }

            $timeInterval = 10;
            for ($page = floor($point / $limit); $page <= ceil($endPoint / $limit); $page++) {
                \Workerman\Timer::add($timeInterval, function () use ($page, $limit) {
                    static::handleData($page, $limit);
                }, '', false);
                $timeInterval += 10;
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 处理数据
     *
     * @author HSK
     * @date 2022-07-15 15:15:15
     *
     * @param integer $page
     * @param integer $limit
     *
     * @return void
     */
    protected static function handleData(int $page, int $limit)
    {
        try {
            $tracingList = Db::name('tracing')
                ->field('day, time, trace, project, ip, transfer, cost_time, success, code, details')
                ->limit($limit)
                ->page($page)
                ->select()
                ->toArray();

            $insertElasticsearchData = ['body' => []];
            foreach ($tracingList as $item) {
                if (Elasticsearch::exists([
                    'index' => config('elasticsearch.index', 'tracing'),
                    'id'    => $item['trace'],
                ])) {
                    continue;
                }

                $insertElasticsearchData['body'][] = [
                    'index' => [
                        '_index' => config('elasticsearch.index', 'tracing'),
                        '_id'    => $item['trace']
                    ]
                ];
                $insertElasticsearchData['body'][] = ['sort' => strtotime($item['time'])] + $item;
            }
            if (!empty($insertElasticsearchData['body'])) {
                Elasticsearch::bulk($insertElasticsearchData);
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 二分获取起始位置
     *
     * @author HSK
     * @date 2022-07-15 14:45:35
     *
     * @param integer $startPoint
     * @param integer $endPoint
     *
     * @return integer
     */
    protected static function binaryStartPoint(int $startPoint, int $endPoint): int
    {
        if (
            $startPoint === $endPoint ||
            1 === abs($endPoint - $startPoint)
        ) {
            return $startPoint;
        }

        $midPoint = floor(($endPoint + $startPoint) / 2);
        while (true) {
            $tracing = Db::name('tracing')->field('trace')->limit(1)->page($midPoint)->select()->toArray();
            if (!empty($tracing)) {
                break;
            }
            --$midPoint;
        }
        if ((int)$midPoint === (int)$startPoint) {
            return $midPoint;
        }

        if (Elasticsearch::exists(['index' => config('elasticsearch.index', 'tracing'), 'id' => $tracing[0]['trace']])) {
            return static::binaryStartPoint($midPoint, $endPoint);
        } else {
            return static::binaryStartPoint($startPoint, $midPoint);
        }
    }
}
