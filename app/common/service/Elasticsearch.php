<?php

namespace app\common\service;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use support\Log;

class Elasticsearch
{
    /**
     * @var Client
     */
    protected static $_instance = null;

    /**
     * 初始化
     *
     * @author HSK
     * @date 2022-07-11 16:44:07
     *
     * @return Client
     */
    public static function instance(): Client
    {
        if (!static::$_instance) {
            static::$_instance = ClientBuilder::create()
                ->setHosts([
                    [
                        'host'   => config('elasticsearch.host'),
                        'port'   => config('elasticsearch.port'),
                        'scheme' => config('elasticsearch.scheme'),
                        'user'   => config('elasticsearch.user'),
                        'pass'   => config('elasticsearch.pass'),
                    ]
                ])
                ->setSSLVerification(config('elasticsearch.ssl_cert'))
                ->setLogger(Log::channel('elasticsearch'))
                ->build();
        }

        return static::$_instance;
    }

    /**
     * 获取基本信息
     *
     * @author HSK
     * @date 2022-07-11 16:57:06
     *
     * @return array
     */
    public static function info(): array
    {
        return static::instance()->info();
    }

    /**
     * 检查索引是否存在
     *
     * @author HSK
     * @date 2022-07-11 17:22:15
     *
     * @param string $index
     * @param array $extra
     *
     * @return boolean
     */
    public static function indicesExists($index, $extra = []): bool
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->indices()->exists($params);
    }

    /**
     * 创建索引
     *
     * @author HSK
     * @date 2022-07-11 17:25:40
     *
     * @param string $index
     * @param array $extra
     *
     * @return array
     */
    public static function indicesCreate($index, $extra = []): array
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->indices()->create($params);
    }

    /**
     * 删除索引
     *
     * @author HSK
     * @date 2022-07-11 17:26:47
     *
     * @param string $index
     * @param array $extra
     *
     * @return array
     */
    public static function indicesDelete($index, $extra = []): array
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->indices()->delete($params);
    }

    /**
     * 获取索引的配置参数
     *
     * @author HSK
     * @date 2022-07-11 17:37:29
     *
     * @param string|array $index
     * @param array $extra
     *
     * @return array
     */
    public static function indicesGetSettings($index, $extra = []): array
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->indices()->getSettings($params);
    }

    /**
     * 更改索引的配置参数
     *
     * @author HSK
     * @date 2022-07-12 11:14:53
     *
     * @param string $index
     * @param array $extra
     *
     * @return array
     */
    public static function indicesPutSettings($index, $extra = []): array
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->indices()->putSettings($params);
    }

    /**
     * 获取索引统计信息
     *
     * @author HSK
     * @date 2022-07-18 11:23:06
     *
     * @param string $index
     * @param array $extra
     *
     * @return array
     */
    public static function indicesStats($index, $extra = []): array
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->indices()->stats($params);
    }

    /**
     * 文档索引
     *
     * @author HSK
     * @date 2022-07-12 11:38:53
     *
     * @param array $params
     *
     * @return array
     */
    public static function index($params = []): array
    {
        return static::instance()->index($params);
    }

    /**
     * 文档批量索引
     *
     * @author HSK
     * @date 2022-07-12 11:39:48
     *
     * @param array $params
     *
     * @return array
     */
    public static function bulk($params = []): array
    {
        return static::instance()->bulk($params);
    }

    /**
     * 更新文档
     *
     * @author HSK
     * @date 2022-07-12 14:39:36
     *
     * @param array $params
     *
     * @return array
     */
    public static function update($params = []): array
    {
        return static::instance()->update($params);
    }

    /**
     * 获取文档
     *
     * @author HSK
     * @date 2022-07-12 11:40:01
     *
     * @param array $params
     *
     * @return array
     */
    public static function get($params = []): array
    {
        return static::instance()->get($params);
    }

    /**
     * 删除文档
     *
     * @author HSK
     * @date 2022-07-12 11:56:15
     *
     * @param array $params
     *
     * @return array
     */
    public static function delete($params = []): array
    {
        return static::instance()->delete($params);
    }

    /**
     * 查询并删除文档
     *
     * @author HSK
     * @date 2022-07-19 17:17:22
     *
     * @param array $params
     *
     * @return array
     */
    public static function deleteByQuery($params = []): array
    {
        return static::instance()->deleteByQuery($params);
    }

    /**
     * 检查文档是否存在
     *
     * @author HSK
     * @date 2022-07-12 11:56:15
     *
     * @param array $params
     *
     * @return bool
     */
    public static function exists($params = []): bool
    {
        return static::instance()->exists($params);
    }

    /**
     * 获取索引匹配的文档数
     *
     * @author HSK
     * @date 2022-07-12 16:53:26
     *
     * @param string $index
     * @param array $extra
     *
     * @return array
     */
    public static function count($index, $extra = []): array
    {
        $params = ['index' => $index] + $extra;
        return static::instance()->count($params);
    }

    /**
     * 搜索
     *
     * @author HSK
     * @date 2022-07-12 14:00:52
     *
     * @param array $params
     *
     * @return array
     */
    public static function search($params = []): array
    {
        return static::instance()->search($params);
    }

    /**
     * 游标查询
     *
     * @author HSK
     * @date 2022-07-12 14:59:08
     *
     * @param array $params
     *
     * @return array
     */
    public static function scroll($params = []): array
    {
        return static::instance()->scroll($params);
    }
}
