<?php

namespace app\queue\redis\elasticsearch;

use app\common\service\Elasticsearch;
use app\common\service\Cache;

class InsertIndex implements \Webman\RedisQueue\Consumer
{
    /**
     * 数据记录
     *
     * @var string
     */
    public $queue = 'elasticsearch_insert_index';

    /**
     * 连接名
     *
     * @var string
     */
    public $connection = 'elasticsearch';

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
     * @date 2022-07-12 23:56:51
     *
     * @param array $data
     *
     * @return void
     */
    public function consume($data)
    {
        try {
            if (!config('elasticsearch.enable', false)) {
                return;
            }

            if (Elasticsearch::exists([
                'index' => config('elasticsearch.index', 'tracing'),
                'id'    => $data['trace'],
            ])) {
                return;
            }

            Cache::$insertElasticsearchDataCache['body'][] = [
                'index' => [
                    '_index' => config('elasticsearch.index', 'tracing'),
                    '_id' => $data['trace']
                ]
            ];
            Cache::$insertElasticsearchDataCache['body'][] = ['sort' => strtotime($data['time'])] + $data;

            if (
                $this->_lastInsertTime < (time() - 15) ||
                count(Cache::$insertElasticsearchDataCache['body']) > 1000
            ) {
                $this->insert();
                $this->_lastInsertTime = time();
            }

            static $initialized;
            if (!$initialized) {
                \Workerman\Timer::add(30, function () {
                    if (empty(Cache::$insertElasticsearchDataCache['body'])) {
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
     * @date 2022-07-13 15:53:29
     *
     * @return void
     */
    public static function insert()
    {
        try {
            if (!config('elasticsearch.enable', false)) {
                return;
            }

            if (!Elasticsearch::indicesExists(config('elasticsearch.index', 'tracing'))) {
                Elasticsearch::indicesCreate(config('elasticsearch.index', 'tracing'), [
                    'body' => [
                        'settings' => [
                            'max_result_window' => 1000000
                        ]
                    ]
                ]);
            }

            Elasticsearch::bulk(Cache::$insertElasticsearchDataCache);
            Cache::$insertElasticsearchDataCache = ['body' => []];
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }
}
