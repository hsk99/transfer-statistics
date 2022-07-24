<?php

namespace app\queue\redis\mysql;

use think\facade\Db;
use app\common\service\Cache;

class InsertTracing implements \Webman\RedisQueue\Consumer
{
    /**
     * 数据记录
     *
     * @var string
     */
    public $queue = 'mysql_insert_tracing';

    /**
     * 连接名
     *
     * @var string
     */
    public $connection = 'mysql';

    /**
     * 上次添加时间
     *
     * @var int
     */
    protected $_lastInsertTime = 0;

    /**
     * 消费
     *
     * @author HSK
     * @date 2022-06-16 15:44:47
     *
     * @param array $data
     *
     * @return void
     */
    public function consume($data)
    {
        try {
            Cache::$insertTracingDataCache[] = $data;

            if (
                $this->_lastInsertTime < (time() - 15) ||
                count(Cache::$insertTracingDataCache) > 1000
            ) {
                $this->insert();
                $this->_lastInsertTime = time();
            }

            static $initialized;
            if (!$initialized) {
                \Workerman\Timer::add(30, function () {
                    if (empty(Cache::$insertTracingDataCache)) {
                        return;
                    }

                    try {
                        $this->insert();
                    } catch (\Throwable $th) {
                        \Hsk99\WebmanException\RunException::report($th);
                    }
                });

                $initialized = true;
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 执行添加
     *
     * @author HSK
     * @date 2022-07-13 16:15:35
     *
     * @return void
     */
    public static function insert()
    {
        try {
            Db::name('tracing')->limit(500)->insertAll(Cache::$insertTracingDataCache);
            Cache::$insertTracingDataCache = [];
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }
}
