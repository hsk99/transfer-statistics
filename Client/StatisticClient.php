<?php

/**
 * 统计上报Client
 *
 * @author HSK
 * @date 2021-12-12 22:19:38
 */
class StatisticClient
{
    /**
     * 包头长度
     */
    const PACKAGE_FIXED_LENGTH = 18;

    /**
     * UDP 包最大长度
     */
    const MAX_UDP_PACKGE_SIZE = 65507;

    /**
     * char类型存储最大值
     */
    const MAX_CHAR_VALUE = 255;

    /**
     * 上报记时
     *
     * @var array
     */
    protected static $timeMap = [];

    /**
     * 上报地址
     *
     * @var string
     */
    public static $remoteAddress = '';

    /**
     * 消耗时间开始计时
     *
     * @author HSK
     * @date 2021-12-12 22:24:35
     *
     * @param string $project
     * @param string $ip
     * @param string $transfer
     *
     * @return string
     */
    public static function tick(string $project = '', string $ip, string $transfer = ''): string
    {
        $unique = uniqid();

        self::$timeMap[md5($project . $ip . $transfer . $unique)] = microtime(true);

        return $unique;
    }

    /**
     * 上报数据
     *
     * @author HSK
     * @date 2021-12-12 22:25:15
     *
     * @param string $unique
     * @param string $project
     * @param string $ip
     * @param string $transfer
     * @param boolean $success
     * @param integer $code
     * @param string $details
     * @param float $cost
     *
     * @return boolean
     */
    public static function report(string $unique = '', string $project, string $ip, string $transfer, bool $success, int $code, string $details, float $cost = 0): bool
    {
        $finishTime = microtime(true);
        $startTime  = self::$timeMap[md5($project . $ip . $transfer . $unique)] ?? microtime(true);
        unset(self::$timeMap[md5($project . $ip . $transfer . $unique)]);

        $costTime = $finishTime - $startTime;

        if (empty($unique) && isset($cost)) {
            $costTime = $cost;
        }

        $binData = self::encode($project, $ip, $transfer, $costTime, $success, $code, $details);

        return self::sendData($binData);
    }

    /**
     * 数据打包
     *
     * @author HSK
     * @date 2021-12-12 22:25:30
     *
     * @param string $project
     * @param string $ip
     * @param string $transfer
     * @param float $costTime
     * @param boolean $success
     * @param integer $code
     * @param string $details
     *
     * @return string
     */
    public static function encode(string $project, string $ip, string $transfer, float $costTime, bool $success, int $code = 0, string $details = ''): string
    {
        if (strlen($project) > self::MAX_CHAR_VALUE) {
            $project = substr($project, 0, self::MAX_CHAR_VALUE);
        }

        if (strlen($transfer) > self::MAX_CHAR_VALUE) {
            $transfer = substr($transfer, 0, self::MAX_CHAR_VALUE);
        }

        if (strlen($ip) > self::MAX_CHAR_VALUE) {
            $ip = substr($ip, 0, self::MAX_CHAR_VALUE);
        }

        $projectLen  = strlen($project);
        $ipLen       = strlen($ip);
        $transferLen = strlen($transfer);
        $detailsLen  = self::MAX_UDP_PACKGE_SIZE - self::PACKAGE_FIXED_LENGTH - $projectLen - $transferLen;

        if (strlen($details) > $detailsLen) {
            $details = substr($details, 0, $detailsLen);
        }

        return pack('CCCfCNnN', $projectLen, $ipLen, $transferLen, $costTime, $success ? 1 : 0, $code, strlen($details), time()) . $project . $ip . $transfer . $details;
    }

    /**
     * 发送数据
     *
     * @author HSK
     * @date 2021-12-12 22:25:55
     *
     * @param string $buffer
     *
     * @return boolean
     */
    protected static function sendData(string $buffer): bool
    {
        try {
            if (empty(self::$remoteAddress)) {
                self::$remoteAddress = config('app.statisticAddress');
            }

            $socket = stream_socket_client(self::$remoteAddress);

            if (!$socket) {
                return false;
            }

            return stream_socket_sendto($socket, $buffer) == strlen($buffer);
        } catch (\Throwable $th) {
            return false;
        }
    }
}
