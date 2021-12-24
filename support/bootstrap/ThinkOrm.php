<?php

namespace support\bootstrap;

use Webman\Bootstrap;
use Workerman\Timer;
use think\facade\Db;
use support\Log;

class ThinkOrm implements Bootstrap
{
    /**
     * 进程启动调用
     *
     * @author HSK
     * @date 2021-11-15 16:16:30
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public static function start($worker)
    {
        // 初始化数据库
        Db::setConfig(config('database'));

        // 维持mysql心跳
        Timer::add(55, function () {
            $connections = config('database.connections', []);
            foreach ($connections as $key => $item) {
                if ($item['type'] == 'mysql') {
                    Db::connect($key)->query('select 1');
                }
            }
        });

        // 监听SQL，并记录日志
        Db::listen(function ($sql, $runtime, $master) {
            switch (true) {
                case is_numeric($runtime):
                    $transfer = $sql;
                    $cost     = $runtime;
                    break;
                case !is_numeric($runtime) && 'CONNECT' === substr($sql, 0, 7):
                    @preg_match("/UseTime:([0-9]+(\\.[0-9]+)?|[0-9]+(\\.[0-9]+))/", $sql, $result);
                    if (count($result) > 1) {
                        $transfer = substr($sql, strpos($sql, "s ] ") + 4);
                        $cost     = $result[1];
                    } else {
                        $transfer = $sql;;
                        $cost     = 0;
                    }
                    break;
                default:
                    $transfer = $sql;;
                    $cost     = 0;
                    break;
            }
            \expand\StatisticClient::report('', 'testSql', '127.0.0.1', $transfer, true, 1, json_encode([
                'sql'     => $sql,
                'runtime' => $cost . 's',
                'master'  => $master,
            ], 320), $cost);


            $time = microtime(true);

            if ($sql === 'select 1') {
                return;
            }

            $sqlLog = [
                'time'     => date('Y-m-d H:i:s.', $time) . substr($time, 11),   // 请求时间（包含毫秒时间）
                'channel'  => 'sql',                                             // 日志通道
                'level'    => 'DEBUG',                                           // 日志等级
                'message'  => '',                                                // 描述
                'sql'      => $sql,                                              // SQL语句
                'run_time' => $runtime,                                          // 运行时长
                'master'   => $master,                                           // 主从标识
            ];

            Log::channel('sql')->debug('', $sqlLog);
        });
    }
}
