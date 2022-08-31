<?php

namespace process;

class SyncIndexToElasticSearch
{
    /**
     * @author HSK
     * @date 2022-08-30 15:22:53
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public function onWorkerStart(\Workerman\Worker $worker)
    {
        $workerId    = $worker->id;
        $workerCount = $worker->count;

        \Workerman\Timer::add(10, function () use ($workerId, $workerCount) {
            \app\common\service\SyncIndexToElasticSearch::run($workerId, $workerCount);
        }, '', false);

        \Workerman\Timer::add(3600, function () use ($workerId, $workerCount) {
            \app\common\service\SyncIndexToElasticSearch::run($workerId, $workerCount);
        });
    }
}
