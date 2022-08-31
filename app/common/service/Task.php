<?php

namespace app\common\service;

use support\Redis;
use think\facade\Db;
use app\common\service\Elasticsearch;

class Task
{
    /**
     * 调用信息存储至MySql
     *
     * @author HSK
     * @date 2022-08-26 13:40:35
     *
     * @return void
     */
    public static function tracingInsertMySql()
    {
        try {
            if (Redis::setNx('TracingInsertMySqlCacheLock', 1)) {
                try {
                    Redis::expire('TracingInsertMySqlCacheLock', 10);

                    $count = (int)Redis::lLen('TracingInsertMySqlCache');
                    if (0 === $count) {
                        return;
                    } else if ($count > 5000) {
                        $count = 5000;
                    }

                    $tracingList = Redis::lRange('TracingInsertMySqlCache', 0, $count - 1);
                    Redis::lTrim('TracingInsertMySqlCache', $count, -1);
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                    return;
                } finally {
                    Redis::del('TracingInsertMySqlCacheLock');
                }
            } else {
                \Workerman\Timer::add(0.1, function () {
                    static::tracingInsertMySql();
                }, '', false);
                return;
            }

            $tracingList = array_map(function ($item) {
                return json_decode($item, true);
            }, $tracingList);

            Db::name('tracing')->limit(500)->insertAll($tracingList);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 调用信息同步至ELasticsearch
     *
     * @author HSK
     * @date 2022-08-26 13:53:33
     *
     * @return void
     */
    public static function indexInsertElasticSearch()
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

            if (Redis::setNx('IndexInsertElasticSearchCacheLock', 1)) {
                try {
                    Redis::expire('IndexInsertElasticSearchCacheLock', 10);

                    $count = (int)Redis::lLen('IndexInsertElasticSearchCache');
                    if (0 === $count) {
                        return;
                    } else if ($count > 1000) {
                        $count = 1000;
                    }

                    $indexList = Redis::lRange('IndexInsertElasticSearchCache', 0, $count - 1);
                    Redis::lTrim('IndexInsertElasticSearchCache', $count, -1);
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                    return;
                } finally {
                    Redis::del('IndexInsertElasticSearchCacheLock');
                }
            } else {
                \Workerman\Timer::add(0.1, function () {
                    static::indexInsertElasticSearch();
                }, '', false);
                return;
            }

            $insertElasticsearchData = ['body' => []];
            foreach ($indexList as $item) {
                $item = json_decode($item, true);

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
}
