<?php

namespace support\exception;

use Throwable;
use support\Log;
use Webman\RedisQueue\Client;

/**
 * 运行异常处理
 *
 * @author HSK
 * @date 2021-10-20 10:46:04
 */
class RunException
{
    /**
     * 记录
     *
     * @author HSK
     * @date 2021-10-20 10:47:16
     *
     * @param Throwable $exception
     *
     * @return void
     */
    public static function report(Throwable $exception)
    {
        // 记录日志
        Log::warning($exception->getMessage(), ['exception' => (string)$exception]);

        // 发送邮件
        if (config('app.SendRunExceptionToMail', false)) {
            $queue = 'SendEmail';
            $data  = [
                'mail'    => '3024186605@qq.com',
                'subject' => 'statistic RunException',
                'body'    => '<p style="color:red;">Error time：' . date('Y-m-d H:i:s') . '</p><p style="color:red;">WorkerPid：' . @posix_getpid() . '</p><br>' . (string)$exception,
            ];

            Client::send($queue, $data);
        }
    }
}
