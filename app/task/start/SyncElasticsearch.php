<?php

namespace app\task\start;

use think\facade\Db;
use app\common\service\Elasticsearch;
use Workerman\Timer;

class SyncElasticsearch
{
    /**
     * @author HSK
     * @date 2022-07-13 16:43:24
     *
     * @param \Workerman\Worker $worker
     */
    public function __construct(\Workerman\Worker $worker)
    {
        $workerId    = $worker->id;
        $workerCount = $worker->count;
        if ($workerCount > 1 && 0 === $worker->id) {
            return;
        }

        if (config('elasticsearch.enable', false)) {
            Timer::add(10, function () use ($workerId, $workerCount) {
                $this->sync($workerId, $workerCount);
            }, '', false);

            Timer::add(3600, function () use ($workerId, $workerCount) {
                $this->sync($workerId, $workerCount);
            });
        }
    }

    /**
     * 同步数据
     *
     * @author HSK
     * @date 2022-07-13 16:45:43
     *
     * @param integer $workerId
     * @param integer $workerCount
     * 
     * @return void
     */
    protected function sync(int $workerId, int $workerCount)
    {
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
                $point    = $this->binaryStartPoint(0, $tracingDbCount);
                $endPoint = $tracingDbCount;
            } else {
                $endPoint = $workerId * $workerHandleCount;
                $point    = $this->binaryStartPoint(($workerId - 1) * $workerHandleCount, $endPoint);
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
                Timer::add($timeInterval, function () use ($page, $limit) {
                    $this->handleData($page, $limit);
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
    protected function handleData(int $page, int $limit)
    {
        try {
            $tracingList = Db::name('tracing')
                ->field('day, time, trace, project, ip, transfer, cost_time, success, code, details')
                ->limit($limit)
                ->page($page)
                ->select()
                ->toArray();
            array_map(function ($item) {
                if (!Elasticsearch::exists([
                    'index' => config('elasticsearch.index', 'tracing'),
                    'id'    => $item['trace'],
                ])) {
                    \Webman\RedisQueue\Client::connection('elasticsearch')->send('elasticsearch_insert_index', $item);
                }
            }, $tracingList);
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
    protected function binaryStartPoint(int $startPoint, int $endPoint): int
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
            return $this->binaryStartPoint($midPoint, $endPoint);
        } else {
            return $this->binaryStartPoint($startPoint, $midPoint);
        }
    }
}
