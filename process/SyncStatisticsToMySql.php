<?php

namespace process;

class SyncStatisticsToMySql
{
    /**
     * @author HSK
     * @date 2022-08-30 15:12:33
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public function onWorkerStart(\Workerman\Worker $worker)
    {
        \Workerman\Timer::add(3, function () {
            \app\common\service\SyncStatisticsToMySql::run(date('Ymd', time()));
        }, '', false);

        \Workerman\Timer::add(\app\common\service\SyncStatisticsToMySql::$updateCycle, function () {
            \app\common\service\SyncStatisticsToMySql::run(date('Ymd', time()));
        });

        new \Workerman\Crontab\Crontab('1 1 0 * * *', function () {
            \app\common\service\SyncStatisticsToMySql::run(date('Ymd', time() - 300));
        });
    }
}
