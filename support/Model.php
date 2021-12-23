<?php

namespace support;

use think\Model as BaseModel;
use support\Db;
use support\Redis;

class Model extends BaseModel
{
    /**
     * 自增值存储Redis Key
     */
    const REDIS_KEY = 'KeyCode';

    /**
     * 新增前
     *
     * @author HSK
     * @date 2021-11-22 09:22:12
     *
     * @param \think\Model $model
     *
     * @return void
     */
    public static function onBeforeInsert($model)
    {
        // 获取操作表名
        $table = $model->table;

        // 验证是否定义模型唯一标识，用于生成主键ID
        if (!empty(config('table_unique.' . $table))) {
            try {
                $unique = (int)config('table_unique.' . $table);

                // 校验数据
                if ($unique > 999) $unique = 999;

                // 获取自增ID
                $result = self::getId($unique);
                if ($result['code'] !== 200) {
                    throw new \Exception($result['msg'], 500);
                }
                if ($result['id'] > 99999999999) {
                    throw new \Exception('id 长度超出限制', 500);
                }

                // 生成唯一ID
                $id = 100000000000000;
                $id += $unique * 100000000000;
                $id += $result['id'];

                // 设置新增主键ID
                $model->id = $id;
            } catch (\Throwable $th) {
                throw new \Exception($th->getMessage(), 500);
            }
        }
    }

    /**
     * 获取数据自增ID
     *
     * @author HSK
     * @date 2021-11-22 09:40:21
     *
     * @param int $hashKey
     *
     * @return array
     */
    protected static function getId(int $hashKey): array
    {
        try {
            // $hashKey 对应的自增值不存在时，初始化起始值
            if (!Redis::hExists(self::REDIS_KEY, $hashKey)) {
                if (!$value = Db::table('key_code')->where('key', $hashKey)->value('value')) {
                    $value = 0;
                }
                Redis::hSetNx(self::REDIS_KEY, $hashKey, $value);
            }

            if ($id = Redis::hIncrBy(self::REDIS_KEY, $hashKey, 1)) {
                return [
                    'code' => 200,
                    'id'   => $id
                ];
            } else {
                return [
                    'code' => 401,
                    'msg'  => '获取失败，请稍候再试'
                ];
            }
        } catch (\Throwable $th) {
            return [
                'code' => 400,
                'msg'  => $th->getMessage()
            ];
        }
    }
}
