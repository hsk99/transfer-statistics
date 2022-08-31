<?php

namespace app\common\service;

use support\Redis;
use think\facade\Db;

class SyncStatisticsToMySql
{
    /**
     * @var integer
     */
    public static $updateCycle = 60;

    /**
     * @var boolean
     */
    protected static $_isHandle = false;

    /**
     * @var array
     */
    protected static $_cache = [];

    /**
     * 同步统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:21:14
     *
     * @param string $day
     *
     * @return void
     */
    public static function run($day)
    {
        try {
            // 应用
            $projectList = Redis::hGetAll('TransferStatistics:project') ?: [];
            // 耗时
            $statisticData['cost'] = Redis::hGet('TransferStatistics:statistic:cost', $day) ?: 0;
            // 次数
            $statisticData['count'] = Redis::hGet('TransferStatistics:statistic:count', $day) ?: 0;
            // 成功次数
            $statisticData['success_count'] = Redis::hGet('TransferStatistics:statistic:success_count', $day) ?: 0;
            // 失败次数
            $statisticData['error_count'] = Redis::hGet('TransferStatistics:statistic:error_count', $day) ?: 0;

            if (Db::name('statistic')->where('day', $day)->value('day')) {
                Db::name('statistic')->where('day', $day)->update($statisticData);
            } else {
                $statisticData['day'] = $day;
                Db::name('statistic')->insert($statisticData);
            }

            $oldProjectList = Db::name('project')->column('project');
            $newProjectList = array_diff($projectList, $oldProjectList);
            $newProjectList = array_map(function ($project) {
                return ['project' => $project];
            }, $newProjectList);
            if (!empty($newProjectList)) {
                Db::name('project')->insertAll($newProjectList);
            }

            static::projectStatistics($day, $projectList);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 同步应用统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:21:21
     *
     * @param string $day
     * @param array $projectList
     *
     * @return void
     */
    protected static function projectStatistics($day, array $projectList)
    {
        try {
            static::$_isHandle = false;
            if (date('Ymd', time()) == $day) {
                static::$_isHandle = true;
            }

            static::$_cache = [];

            $oldProjectList = Db::name('statistic_project')->where('day', $day)->column('id', 'project');
            foreach ($projectList as $project) {
                // 耗时
                $statisticProjectData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:cost', $day) ?: 0;
                // 次数
                $statisticProjectData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:count', $day) ?: 0;
                // 成功次数
                $statisticProjectData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:success_count', $day) ?: 0;
                // 失败次数
                $statisticProjectData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:error_count', $day) ?: 0;

                try {
                    if (!empty($oldProjectList[$project])) {
                        Db::name('statistic_project')->where('id', $oldProjectList[$project])->update($statisticProjectData);
                    } else {
                        $statisticProjectData['day']     = $day;
                        $statisticProjectData['project'] = $project;

                        static::$_cache['statisticProjectNewData'][] = $statisticProjectData;
                    }
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }

                if (static::$_isHandle) {
                    // 同步最近每分钟统计数据，用于图表展示
                    for ($i = 1; $i <= ceil(static::$updateCycle / 60); $i++) {
                        $time = date('YmdHi', strtotime("-$i minute"));

                        $statisticProjectIntervalData = [
                            'day'           => $day,
                            'time'          => $time,
                            'project'       => $project,
                            'count'         => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:count:' . $day, $time) ?: 0,
                            'cost'          => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:cost:' . $day, $time) ?: 0,
                            'success_count' => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:success_count:' . $day, $time) ?: 0,
                            'error_count'   => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:error_count:' . $day, $time) ?: 0,
                        ];

                        static::$_cache['statisticProjectIntervalNewData'][] = $statisticProjectIntervalData;
                    }
                }

                static::projectTransferStatistics($day, $project);
                static::projectCodeStatistics($day, $project);
                static::projectIpStatistics($day, $project);
            }

            Db::name('statistic_project')->limit(500)->insertAll(static::$_cache['statisticProjectNewData'] ?? []);
            Db::name('statistic_project_interval')->limit(500)->insertAll(static::$_cache['statisticProjectIntervalNewData'] ?? []);
            Db::name('statistic_project_transfer')->limit(500)->insertAll(static::$_cache['statisticProjectTransferNewData'] ?? []);
            Db::name('statistic_project_transfer_interval')->limit(500)->insertAll(static::$_cache['statisticProjectTransferIntervalNewData'] ?? []);
            Db::name('statistic_project_code')->limit(500)->insertAll(static::$_cache['statisticProjectCodeNewData'] ?? []);
            Db::name('statistic_project_code_interval')->limit(500)->insertAll(static::$_cache['statisticProjectCodeIntervalNewData'] ?? []);
            Db::name('statistic_project_ip')->limit(500)->insertAll(static::$_cache['statisticProjectIpNewData'] ?? []);
            Db::name('statistic_project_ip_interval')->limit(500)->insertAll(static::$_cache['statisticProjectIpIntervalNewData'] ?? []);
            Db::name('statistic_project_ip_transfer')->limit(500)->insertAll(static::$_cache['statisticProjectIpTransferNewData'] ?? []);
            Db::name('statistic_project_ip_transfer_interval')->limit(500)->insertAll(static::$_cache['statisticProjectIpTransferIntervalNewData'] ?? []);
            Db::name('statistic_project_ip_code')->limit(500)->insertAll(static::$_cache['statisticProjectIpCodeNewData'] ?? []);
            Db::name('statistic_project_ip_code_interval')->limit(500)->insertAll(static::$_cache['statisticProjectIpCodeIntervalNewData'] ?? []);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 组装应用调用统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:21:55
     *
     * @param string $day
     * @param string $project
     *
     * @return void
     */
    protected static function projectTransferStatistics($day, string $project)
    {
        try {
            $projectTransferList = Redis::hGetAll('TransferStatistics:project:' . $project . ':transfer:' . $day) ?: [];


            $oldProjectTransferList = Db::name('statistic_project_transfer')->where('day', $day)->where('project', $project)->column('id', 'transfer');
            foreach ($projectTransferList as $transfer) {
                $transferTemp = str_replace(['::', ':'], '@', $transfer);

                // 耗时
                $statisticProjectTransferData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp, $day) ?: 0;
                // 次数
                $statisticProjectTransferData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp, $day) ?: 0;
                // 成功次数
                $statisticProjectTransferData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp, $day) ?: 0;
                // 失败次数
                $statisticProjectTransferData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp, $day) ?: 0;

                try {
                    if (!empty($oldProjectTransferList[$transfer])) {
                        Db::name('statistic_project_transfer')->where('id', $oldProjectTransferList[$transfer])->update($statisticProjectTransferData);
                    } else {
                        $statisticProjectTransferData['day']      = $day;
                        $statisticProjectTransferData['project']  = $project;
                        $statisticProjectTransferData['transfer'] = $transfer;

                        static::$_cache['statisticProjectTransferNewData'][] = $statisticProjectTransferData;
                    }
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }

                if (static::$_isHandle) {
                    // 同步最近每分钟统计数据，用于图表展示
                    for ($i = 1; $i <= ceil(static::$updateCycle / 60); $i++) {
                        $time = date('YmdHi', strtotime("-$i minute"));

                        $statisticProjectTransferIntervalData = [
                            'day'           => $day,
                            'time'          => $time,
                            'project'       => $project,
                            'transfer'      => $transfer,
                            'count'         => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $time) ?: 0,
                            'cost'          => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $time) ?: 0,
                            'success_count' => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $time) ?: 0,
                            'error_count'   => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $time) ?: 0,
                        ];

                        static::$_cache['statisticProjectTransferIntervalNewData'][] = $statisticProjectTransferIntervalData;
                    }
                }
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 组装应用状态码统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:22:42
     *
     * @param string $day
     * @param string $project
     *
     * @return void
     */
    protected static function projectCodeStatistics($day, string $project)
    {
        try {
            $projectCodeList = Redis::hGetAll('TransferStatistics:project:' . $project . ':code:' . $day) ?: [];

            $oldProjectCodeList = Db::name('statistic_project_code')->where('day', $day)->where('project', $project)->column('id', 'code');
            foreach ($projectCodeList as $code) {
                // 耗时
                $statisticProjectCodeData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code, $day) ?: 0;
                // 次数
                $statisticProjectCodeData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code, $day) ?: 0;

                try {
                    if (!empty($oldProjectCodeList[$code])) {
                        Db::name('statistic_project_code')->where('id', $oldProjectCodeList[$code])->update($statisticProjectCodeData);
                    } else {
                        $statisticProjectCodeData['day']     = $day;
                        $statisticProjectCodeData['project'] = $project;
                        $statisticProjectCodeData['code']    = $code;

                        static::$_cache['statisticProjectCodeNewData'][] = $statisticProjectCodeData;
                    }
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }

                if (static::$_isHandle) {
                    // 同步最近每分钟统计数据，用于图表展示
                    for ($i = 1; $i <= ceil(static::$updateCycle / 60); $i++) {
                        $time = date('YmdHi', strtotime("-$i minute"));

                        $statisticProjectCodeIntervalData = [
                            'day'     => $day,
                            'time'    => $time,
                            'project' => $project,
                            'code'    => $code,
                            'count'   => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code . ':' . $day, $time) ?: 0,
                            'cost'    => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code . ':' . $day, $time) ?: 0,
                        ];

                        static::$_cache['statisticProjectCodeIntervalNewData'][] = $statisticProjectCodeIntervalData;
                    }
                }
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 组装应用IP统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:23:36
     *
     * @param string $day
     * @param string $project
     *
     * @return void
     */
    protected static function projectIpStatistics($day, string $project)
    {
        try {
            $projectIpList = Redis::hGetAll('TransferStatistics:project:' . $project . ':ip:' . $day) ?: [];

            $oldProjectIpList = Db::name('statistic_project_ip')->where('day', $day)->where('project', $project)->column('id', 'ip');
            foreach ($projectIpList as $ip) {
                $ipTemp = str_replace(['::', ':'], '@', $ip);

                // 耗时
                $statisticProjectIpData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp, $day) ?: 0;
                // 次数
                $statisticProjectIpData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp, $day) ?: 0;
                // 成功次数
                $statisticProjectIpData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp, $day) ?: 0;
                // 失败次数
                $statisticProjectIpData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp, $day) ?: 0;

                try {
                    if (!empty($oldProjectIpList[$ip])) {
                        Db::name('statistic_project_ip')->where('id', $oldProjectIpList[$ip])->update($statisticProjectIpData);
                    } else {
                        $statisticProjectIpData['day']     = $day;
                        $statisticProjectIpData['project'] = $project;
                        $statisticProjectIpData['ip']      = $ip;

                        static::$_cache['statisticProjectIpNewData'][] = $statisticProjectIpData;
                    }
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }

                if (static::$_isHandle) {
                    // 同步最近每分钟统计数据，用于图表展示
                    for ($i = 1; $i <= ceil(static::$updateCycle / 60); $i++) {
                        $time = date('YmdHi', strtotime("-$i minute"));

                        $statisticProjectIpIntervalData = [
                            'day'           => $day,
                            'time'          => $time,
                            'project'       => $project,
                            'ip'            => $ip,
                            'count'         => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp . ':' . $day, $time) ?: 0,
                            'cost'          => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp . ':' . $day, $time) ?: 0,
                            'success_count' => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp . ':' . $day, $time) ?: 0,
                            'error_count'   => Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp . ':' . $day, $time) ?: 0,
                        ];

                        static::$_cache['statisticProjectIpIntervalNewData'][] = $statisticProjectIpIntervalData;
                    }
                }

                static::projectClientTransferStatistics($day, $project, $ip);
                static::projectClientCodeStatistics($day, $project, $ip);
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 组装应用Client调用统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:24:05
     *
     * @param string $day
     * @param string $project
     * @param string $ip
     *
     * @return void
     */
    protected static function projectClientTransferStatistics($day, string $project, string $ip)
    {
        try {
            $ipTemp = str_replace(['::', ':'], '@', $ip);
            $projectIpTransferList = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':transfer:' . $day) ?: [];

            $oldProjectIpTransferList = Db::name('statistic_project_ip_transfer')->where('day', $day)->where('project', $project)->where('ip', $ip)->column('id', 'transfer');
            foreach ($projectIpTransferList as $transfer) {
                $transferTemp = str_replace(['::', ':'], '@', $transfer);
                // 耗时
                $statisticProjectIpTransferData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_cost:' . $transferTemp, $day) ?: 0;
                // 次数
                $statisticProjectIpTransferData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_count:' . $transferTemp, $day) ?: 0;
                // 成功次数
                $statisticProjectIpTransferData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_success:' . $transferTemp, $day) ?: 0;
                // 失败次数
                $statisticProjectIpTransferData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_error:' . $transferTemp, $day) ?: 0;

                try {
                    if (!empty($oldProjectIpTransferList[$transfer])) {
                        Db::name('statistic_project_ip_transfer')->where('id', $oldProjectIpTransferList[$transfer])->update($statisticProjectIpTransferData);
                    } else {
                        $statisticProjectIpTransferData['day']      = $day;
                        $statisticProjectIpTransferData['project']  = $project;
                        $statisticProjectIpTransferData['ip']       = $ip;
                        $statisticProjectIpTransferData['transfer'] = $transfer;

                        static::$_cache['statisticProjectIpTransferNewData'][] = $statisticProjectIpTransferData;
                    }
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }

                if (static::$_isHandle) {
                    // 同步最近每分钟统计数据，用于图表展示
                    for ($i = 1; $i <= ceil(static::$updateCycle / 60); $i++) {
                        $time = date('YmdHi', strtotime("-$i minute"));

                        $statisticProjectIpTransferIntervalData = [
                            'day'           => $day,
                            'time'          => $time,
                            'project'       => $project,
                            'ip'            => $ip,
                            'transfer'      => $transfer,
                            'count'         => Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_count:' . $transferTemp . ':' . $day, $time) ?: 0,
                            'cost'          => Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_cost:' . $transferTemp . ':' . $day, $time) ?: 0,
                            'success_count' => Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_success:' . $transferTemp . ':' . $day, $time) ?: 0,
                            'error_count'   => Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_error:' . $transferTemp . ':' . $day, $time) ?: 0,
                        ];

                        static::$_cache['statisticProjectIpTransferIntervalNewData'][] = $statisticProjectIpTransferIntervalData;
                    }
                }
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 组装应用Client状态码统计数据
     *
     * @author HSK
     * @date 2022-08-30 14:24:36
     *
     * @param string $day
     * @param string $project
     * @param string $ip
     *
     * @return void
     */
    protected static function projectClientCodeStatistics($day, string $project, string $ip)
    {
        try {
            $ipTemp = str_replace(['::', ':'], '@', $ip);
            $projectIpCodeList = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':code:' . $day) ?: [];

            $oldProjectIpCodeList = Db::name('statistic_project_ip_code')->where('day', $day)->where('project', $project)->where('ip', $ip)->column('id', 'code');
            foreach ($projectIpCodeList as $code) {
                // 耗时
                $statisticProjectIpCodeData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_cost:' . $code, $day) ?: 0;
                // 次数
                $statisticProjectIpCodeData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_count:' . $code, $day) ?: 0;

                try {
                    if (!empty($oldProjectIpCodeList[$code])) {
                        Db::name('statistic_project_ip_code')->where('id', $oldProjectIpCodeList[$code])->update($statisticProjectIpCodeData);
                    } else {
                        $statisticProjectIpCodeData['day']     = $day;
                        $statisticProjectIpCodeData['project'] = $project;
                        $statisticProjectIpCodeData['ip']      = $ip;
                        $statisticProjectIpCodeData['code']    = $code;

                        static::$_cache['statisticProjectIpCodeNewData'][] = $statisticProjectIpCodeData;
                    }
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }

                if (static::$_isHandle) {
                    // 同步最近每分钟统计数据，用于图表展示
                    for ($i = 1; $i <= ceil(static::$updateCycle / 60); $i++) {
                        $time = date('YmdHi', strtotime("-$i minute"));

                        $statisticProjectIpCodeIntervalData = [
                            'day'           => $day,
                            'time'          => $time,
                            'project'       => $project,
                            'ip'            => $ip,
                            'code'      => $code,
                            'count'         => Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_count:' . $code . ':' . $day, $time) ?: 0,
                            'cost'          => Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_cost:' . $code . ':' . $day, $time) ?: 0,
                        ];

                        static::$_cache['statisticProjectIpCodeIntervalNewData'][] = $statisticProjectIpCodeIntervalData;
                    }
                }
            }
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }
}
