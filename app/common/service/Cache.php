<?php

namespace app\common\service;

use app\queue\redis\mysql\InsertTracing;
use app\queue\redis\elasticsearch\InsertIndex;

class Cache
{
    /**
     * @var array
     */
    public static $insertTracingDataCache = [];

    /**
     * @var array
     */
    public static $insertElasticsearchDataCache = ['body' => []];

    /**
     * 进程启动
     *
     * @author HSK
     * @date 2022-07-13 16:07:15
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public static function start(\Workerman\Worker $worker)
    {
        if ($worker) {
            if (false !== strpos($worker->name, 'plugin.webman.redis-queue')) {
                // RedisQueue进程，接管进程关闭回调
                $worker->onWorkerStop = [static::class, 'onWorkerStop'];
            }
        }
    }

    /**
     * 进程关闭
     *
     * @author HSK
     * @date 2022-07-13 16:09:50
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public static function onWorkerStop(\Workerman\Worker $worker)
    {
        if (count(static::$insertTracingDataCache) > 0) {
            InsertTracing::insert();
        }

        if (count(static::$insertElasticsearchDataCache['body']) > 0) {
            InsertIndex::insert();
        }
    }
}
