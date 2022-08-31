<?php

namespace process;

class TracingInsertMySql
{
    /**
     * @author HSK
     * @date 2022-08-30 15:17:52
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public function onWorkerStart(\Workerman\Worker $worker)
    {
        \Workerman\Timer::add(10 + 0.1 * $worker->id, function () {
            \app\common\service\Task::tracingInsertMySql();
        });
    }
}
