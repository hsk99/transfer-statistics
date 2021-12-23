<?php

namespace Protocols;

class Statistic
{
    /**
     * 包头长度
     */
    const PACKAGE_FIXED_LENGTH = 18;

    /**
     * UDP 包最大长度
     */
    const MAX_UDP_PACKGE_SIZE  = 65507;

    /**
     * char类型存储最大值
     */
    const MAX_CHAR_VALUE = 255;

    /**
     * 分包
     *
     * @author HSK
     * @date 2021-12-12 22:29:26
     *
     * @param string $buffer
     *
     * @return integer
     */
    public static function input(string $buffer): int
    {
        if (strlen($buffer) < self::PACKAGE_FIXED_LENGTH) {
            return 0;
        }

        $result = unpack("CprojectLen/CipLen/CtransferLen/fcostTime/Csuccess/Ncode/ndetailsLen/Ntime", $buffer);

        $len = $result['projectLen'] + $result['ipLen'] + $result['transferLen'] + $result['detailsLen'] + self::PACKAGE_FIXED_LENGTH;

        if (strlen($buffer) < $len) {
            return 0;
        }

        return $len;
    }

    /**
     * 打包
     *
     * @author HSK
     * @date 2021-12-12 22:30:00
     *
     * @param array $buffer
     *
     * @return string
     */
    public static function encode(array $buffer): string
    {
        $project  = $buffer['project'];        // 项目
        $ip       = $buffer['ip'];             // IP
        $transfer = $buffer['transfer'];       // 调用
        $costTime = $buffer['costTime'];       // 耗时
        $success  = $buffer['success'];        // 状态
        $code     = $buffer['code'] ?? 0;      // 状态码
        $details  = $buffer['details'] ?? '';  // 详细信息

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
     * 解包
     *
     * @author HSK
     * @date 2021-12-12 22:30:06
     *
     * @param string $buffer
     *
     * @return array
     */
    public static function decode(string $buffer): array
    {
        $data     = unpack("CprojectLen/CipLen/CtransferLen/fcostTime/Csuccess/Ncode/ndetailsLen/Ntime", $buffer);
        $project  = substr($buffer, self::PACKAGE_FIXED_LENGTH, $data['projectLen']);
        $ip       = substr($buffer, self::PACKAGE_FIXED_LENGTH + $data['projectLen'], $data['ipLen']);
        $transfer = substr($buffer, self::PACKAGE_FIXED_LENGTH + $data['projectLen'] + $data['ipLen'], $data['transferLen']);
        $details  = substr($buffer, self::PACKAGE_FIXED_LENGTH + $data['projectLen'] + $data['ipLen'] + $data['transferLen']);

        return [
            'project'  => $project,
            'ip'       => $ip,
            'transfer' => $transfer,
            'costTime' => $data['costTime'],
            'success'  => $data['success'],
            'time'     => $data['time'],
            'code'     => $data['code'],
            'details'  => $details
        ];
    }
}
