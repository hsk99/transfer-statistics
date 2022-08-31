<?php

namespace process;

class Statistic
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
        \Workerman\Timer::add(1 + 0.01 * $worker->id, function () {
            \app\common\service\Statistic::run();
        });
    }
}
