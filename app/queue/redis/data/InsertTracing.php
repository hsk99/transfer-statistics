<?php

namespace app\queue\redis\data;

class InsertTracing implements \Webman\RedisQueue\Consumer
{
    /**
     * 数据更新
     *
     * @var string
     */
    public $queue = 'data_insert_tracing';

    /**
     * 连接名
     *
     * @var string
     */
    public $connection = 'data';

    /**
     * 数据缓存
     *
     * @var array
     */
    public $dataCache = [];

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
            $this->dataCache[] = $data;

            if (
                $this->_lastInsertTime < (time() - 15) ||
                count($this->dataCache) > 1000
            ) {
                \think\facade\Db::name('tracing')->limit(500)->insertAll($this->dataCache);
                $this->dataCache = [];

                $this->_lastInsertTime = time();
            }

            static $initialized;
            if (!$initialized) {
                \Workerman\Timer::add(30, function () {
                    if (empty($this->dataCache)) {
                        return;
                    }

                    try {
                        \think\facade\Db::name('tracing')->limit(500)->insertAll($this->dataCache);
                        $this->dataCache = [];
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
}
