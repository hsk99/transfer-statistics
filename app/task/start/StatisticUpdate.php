<?php

namespace app\task\start;

use support\Redis;
use think\facade\Db;

class StatisticUpdate
{
    /**
     * @author HSK
     * @date 2022-06-20 09:11:36
     *
     * @param \Workerman\Worker $worker
     */
    public function __construct(\Workerman\Worker $worker)
    {
        if (0 === $worker->id) {
            \Workerman\Timer::add(3, function () {
                $this->totalStatistics(date('Ymd', time()));
            }, '', false);

            \Workerman\Timer::add(30, function () {
                $this->totalStatistics(date('Ymd', time()));
            });

            new \Workerman\Crontab\Crontab('1 1 0 * * *', function () {
                $this->totalStatistics(date('Ymd', time() - 300));
            });
        }
    }

    /**
     * 更新总统计数据
     *
     * @author HSK
     * @date 2022-06-16 11:39:26
     *
     * @param integer $day
     *
     * @return void
     */
    protected function totalStatistics(int $day)
    {
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
        try {
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

            array_map(function ($project) use (&$day) {
                $this->projectStatistics($day, $project);
            }, $projectList);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 更新应用统计数据
     *
     * @author HSK
     * @date 2022-06-20 10:04:09
     *
     * @param integer $day
     * @param string $project
     *
     * @return void
     */
    protected function projectStatistics(int $day, string $project)
    {
        try {
            // 耗时
            $statisticProjectData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:cost', $day) ?: 0;
            // 次数
            $statisticProjectData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:count', $day) ?: 0;
            // 成功次数
            $statisticProjectData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:success_count', $day) ?: 0;
            // 失败次数
            $statisticProjectData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:error_count', $day) ?: 0;

            if ($statisticProjectId = Db::name('statistic_project')->where('day', $day)->where('project', $project)->value('id')) {
                Db::name('statistic_project')->where('id', $statisticProjectId)->update($statisticProjectData);
            } else {
                $statisticProjectData['day']     = $day;
                $statisticProjectData['project'] = $project;
                Db::name('statistic_project')->insert($statisticProjectData);
            }

            // 每分钟统计数据，用于图表展示
            $statisticProjectIntervalCost         = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:cost:' . $day) ?: [];
            $statisticProjectIntervalCount        = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:count:' . $day) ?: [];
            $statisticProjectIntervalSuccessCount = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:success_count:' . $day) ?: [];
            $statisticProjectIntervalErrorCount   = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:error_count:' . $day) ?: [];

            $statisticProjectIntervalOldData = Db::name('statistic_project_interval')->where('day', $day)->where('project', $project)->column('id', 'time');
            $statisticProjectIntervalNewData = [];
            foreach ($statisticProjectIntervalCount as $time => $count) {
                if ($time < date('YmdHi', time() - 300)) {
                    continue;
                }
                $data = [
                    'day'           => $day,
                    'time'          => $time,
                    'project'       => $project,
                    'count'         => $count,
                    'cost'          => $statisticProjectIntervalCost[$time] ?: 0,
                    'success_count' => $statisticProjectIntervalSuccessCount[$time] ?: 0,
                    'error_count'   => $statisticProjectIntervalErrorCount[$time] ?: 0,
                ];
                if (!empty($statisticProjectIntervalOldData[$time])) {
                    Db::name('statistic_project_interval')->where('id', $statisticProjectIntervalOldData[$time])->update($data);
                } else {
                    $statisticProjectIntervalNewData[] = $data;
                }
            }
            Db::name('statistic_project_interval')->limit(500)->insertAll($statisticProjectIntervalNewData);

            $this->projectTransferStatistics($day, $project);
            $this->projectCodeStatistics($day, $project);
            $this->projectIpStatistics($day, $project);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
        }
    }

    /**
     * 更新应用调用统计数据
     *
     * @author HSK
     * @date 2022-06-20 09:48:07
     *
     * @param integer $day
     * @param string $project
     *
     * @return void
     */
    protected function projectTransferStatistics(int $day, string $project)
    {
        $projectTransferList = Redis::hGetAll('TransferStatistics:project:' . $project . ':transfer:' . $day) ?: [];
        array_map(function ($transfer) use (&$project, &$day) {
            try {
                $transferTemp = str_replace(['::', ':'], '@', $transfer);

                // 耗时
                $statisticProjectTransferData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp, $day) ?: 0;
                // 次数
                $statisticProjectTransferData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp, $day) ?: 0;
                // 成功次数
                $statisticProjectTransferData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp, $day) ?: 0;
                // 失败次数
                $statisticProjectTransferData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp, $day) ?: 0;

                if ($statisticProjectTransferId = Db::name('statistic_project_transfer')->where('day', $day)->where('project', $project)->where('transfer', $transfer)->value('id')) {
                    Db::name('statistic_project_transfer')->where('id', $statisticProjectTransferId)->update($statisticProjectTransferData);
                } else {
                    $statisticProjectTransferData['day']      = $day;
                    $statisticProjectTransferData['project']  = $project;
                    $statisticProjectTransferData['transfer'] = $transfer;
                    Db::name('statistic_project_transfer')->insert($statisticProjectTransferData);
                }


                // 每分钟统计数据，用于图表展示
                $statisticProjectTransferIntervalCost         = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:transfer_cost:' . $transferTemp . ':' . $day) ?: [];
                $statisticProjectTransferIntervalCount        = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:transfer_count:' . $transferTemp . ':' . $day) ?: [];
                $statisticProjectTransferIntervalSuccessCount = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:transfer_success:' . $transferTemp . ':' . $day) ?: [];
                $statisticProjectTransferIntervalErrorCount   = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:transfer_error:' . $transferTemp . ':' . $day) ?: [];

                $statisticProjectTransferIntervalOldData = Db::name('statistic_project_transfer_interval')->where('day', $day)->where('project', $project)->where('transfer', $transfer)->column('id', 'time');
                $statisticProjectTransferIntervalNewData = [];
                foreach ($statisticProjectTransferIntervalCount as $time => $count) {
                    if ($time < date('YmdHi', time() - 300)) {
                        continue;
                    }
                    $data = [
                        'day'           => $day,
                        'time'          => $time,
                        'project'       => $project,
                        'transfer'      => $transfer,
                        'count'         => $count,
                        'cost'          => $statisticProjectTransferIntervalCost[$time] ?: 0,
                        'success_count' => $statisticProjectTransferIntervalSuccessCount[$time] ?: 0,
                        'error_count'   => $statisticProjectTransferIntervalErrorCount[$time] ?: 0,
                    ];
                    if (!empty($statisticProjectTransferIntervalOldData[$time])) {
                        Db::name('statistic_project_transfer_interval')->where('id', $statisticProjectTransferIntervalOldData[$time])->update($data);
                    } else {
                        $statisticProjectTransferIntervalNewData[] = $data;
                    }
                }
                Db::name('statistic_project_transfer_interval')->insertAll($statisticProjectTransferIntervalNewData);
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }
        }, $projectTransferList);
    }

    /**
     * 更新应用状态码统计数据
     *
     * @author HSK
     * @date 2022-06-20 09:49:46
     *
     * @param integer $day
     * @param string $project
     *
     * @return void
     */
    protected function projectCodeStatistics(int $day, string $project)
    {
        $projectCodeList = Redis::hGetAll('TransferStatistics:project:' . $project . ':code:' . $day) ?: [];
        array_map(function ($code) use (&$project, &$day) {
            try {
                // 耗时
                $statisticProjectCodeData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code, $day) ?: 0;
                // 次数
                $statisticProjectCodeData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code, $day) ?: 0;

                if ($statisticProjectCodeId = Db::name('statistic_project_code')->where('day', $day)->where('project', $project)->where('code', $code)->value('id')) {
                    Db::name('statistic_project_code')->where('id', $statisticProjectCodeId)->update($statisticProjectCodeData);
                } else {
                    $statisticProjectCodeData['day']     = $day;
                    $statisticProjectCodeData['project'] = $project;
                    $statisticProjectCodeData['code']    = $code;
                    Db::name('statistic_project_code')->insert($statisticProjectCodeData);
                }


                // 每分钟统计数据，用于图表展示
                $statisticProjectCodeIntervalCost  = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:code_cost:' . $code . ':' . $day) ?: [];
                $statisticProjectCodeIntervalCount = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:code_count:' . $code . ':' . $day) ?: [];

                $statisticProjectCodeIntervalOldData = Db::name('statistic_project_code_interval')->where('day', $day)->where('project', $project)->where('code', $code)->column('id', 'time');
                $statisticProjectCodeIntervalNewData = [];
                foreach ($statisticProjectCodeIntervalCount as $time => $count) {
                    if ($time < date('YmdHi', time() - 300)) {
                        continue;
                    }
                    $data = [
                        'day'     => $day,
                        'time'    => $time,
                        'project' => $project,
                        'code'    => $code,
                        'count'   => $count,
                        'cost'    => $statisticProjectCodeIntervalCost[$time] ?: 0,
                    ];
                    if (!empty($statisticProjectCodeIntervalOldData[$time])) {
                        Db::name('statistic_project_code_interval')->where('id', $statisticProjectCodeIntervalOldData[$time])->update($data);
                    } else {
                        $statisticProjectCodeIntervalNewData[] = $data;
                    }
                }
                Db::name('statistic_project_code_interval')->insertAll($statisticProjectCodeIntervalNewData);
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }
        }, $projectCodeList);
    }

    /**
     * 更新应用IP统计数据
     *
     * @author HSK
     * @date 2022-06-20 09:48:48
     *
     * @param integer $day
     * @param string $project
     *
     * @return void
     */
    protected function projectIpStatistics(int $day, string $project)
    {
        $projectIpList = Redis::hGetAll('TransferStatistics:project:' . $project . ':ip:' . $day) ?: [];
        array_map(function ($ip) use (&$project, &$day) {
            try {
                $ipTemp = str_replace(['::', ':'], '@', $ip);

                // 耗时
                $statisticProjectIpData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp, $day) ?: 0;
                // 次数
                $statisticProjectIpData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp, $day) ?: 0;
                // 成功次数
                $statisticProjectIpData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp, $day) ?: 0;
                // 失败次数
                $statisticProjectIpData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp, $day) ?: 0;

                if ($statisticProjectIpId = Db::name('statistic_project_ip')->where('day', $day)->where('project', $project)->where('ip', $ip)->value('id')) {
                    Db::name('statistic_project_ip')->where('id', $statisticProjectIpId)->update($statisticProjectIpData);
                } else {
                    $statisticProjectIpData['day']      = $day;
                    $statisticProjectIpData['project']  = $project;
                    $statisticProjectIpData['ip'] = $ip;
                    Db::name('statistic_project_ip')->insert($statisticProjectIpData);
                }


                // 每分钟统计数据，用于图表展示
                $statisticProjectIpIntervalCost         = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:ip_cost:' . $ipTemp . ':' . $day) ?: [];
                $statisticProjectIpIntervalCount        = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:ip_count:' . $ipTemp . ':' . $day) ?: [];
                $statisticProjectIpIntervalSuccessCount = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:ip_success:' . $ipTemp . ':' . $day) ?: [];
                $statisticProjectIpIntervalErrorCount   = Redis::hGetAll('TransferStatistics:project:' . $project . ':statistic:ip_error:' . $ipTemp . ':' . $day) ?: [];

                $statisticProjectIpIntervalOldData = Db::name('statistic_project_ip_interval')->where('day', $day)->where('project', $project)->where('ip', $ip)->column('id', 'time');
                $statisticProjectIpIntervalNewData = [];
                foreach ($statisticProjectIpIntervalCount as $time => $count) {
                    if ($time < date('YmdHi', time() - 300)) {
                        continue;
                    }
                    $data = [
                        'day'           => $day,
                        'time'          => $time,
                        'project'       => $project,
                        'ip'            => $ip,
                        'count'         => $count,
                        'cost'          => $statisticProjectIpIntervalCost[$time] ?: 0,
                        'success_count' => $statisticProjectIpIntervalSuccessCount[$time] ?: 0,
                        'error_count'   => $statisticProjectIpIntervalErrorCount[$time] ?: 0,
                    ];
                    if (!empty($statisticProjectIpIntervalOldData[$time])) {
                        Db::name('statistic_project_ip_interval')->where('id', $statisticProjectIpIntervalOldData[$time])->update($data);
                    } else {
                        $statisticProjectIpIntervalNewData[] = $data;
                    }
                }
                Db::name('statistic_project_ip_interval')->insertAll($statisticProjectIpIntervalNewData);

                $this->projectClientTransferStatistics($day, $project, $ip);
                $this->projectClientCodeStatistics($day, $project, $ip);
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }
        }, $projectIpList);
    }

    /**
     * 更新应用Client调用统计数据
     *
     * @author HSK
     * @date 2022-06-20 10:01:08
     *
     * @param integer $day
     * @param string $project
     * @param string $ip
     *
     * @return void
     */
    protected function projectClientTransferStatistics(int $day, string $project, string $ip)
    {
        $ipTemp = str_replace(['::', ':'], '@', $ip);
        $projectIpTransferList = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':transfer:' . $day) ?: [];
        array_map(function ($transfer) use (&$project, &$day, &$ip, &$ipTemp) {
            try {
                $transferTemp = str_replace(['::', ':'], '@', $transfer);
                // 耗时
                $statisticProjectIpTransferData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_cost:' . $transferTemp, $day) ?: 0;
                // 次数
                $statisticProjectIpTransferData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_count:' . $transferTemp, $day) ?: 0;
                // 成功次数
                $statisticProjectIpTransferData['success_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_success:' . $transferTemp, $day) ?: 0;
                // 失败次数
                $statisticProjectIpTransferData['error_count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_error:' . $transferTemp, $day) ?: 0;

                if ($statisticProjectTransferId = Db::name('statistic_project_ip_transfer')->where('day', $day)->where('project', $project)->where('ip', $ip)->where('transfer', $transfer)->value('id')) {
                    Db::name('statistic_project_ip_transfer')->where('id', $statisticProjectTransferId)->update($statisticProjectIpTransferData);
                } else {
                    $statisticProjectIpTransferData['day']      = $day;
                    $statisticProjectIpTransferData['project']  = $project;
                    $statisticProjectIpTransferData['ip']       = $ip;
                    $statisticProjectIpTransferData['transfer'] = $transfer;
                    Db::name('statistic_project_ip_transfer')->insert($statisticProjectIpTransferData);
                }


                // 每分钟统计数据，用于图表展示
                $statisticProjectIpTransferIntervalCount        = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_count:' . $transferTemp . ':' . $day) ?: [];
                $statisticProjectIpTransferIntervalCost         = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_cost:' . $transferTemp . ':' . $day) ?: [];
                $statisticProjectIpTransferIntervalSuccessCount = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_success:' . $transferTemp . ':' . $day) ?: [];
                $statisticProjectIpTransferIntervalErrorCount   = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:transfer_error:' . $transferTemp . ':' . $day) ?: [];

                $statisticProjectIpTransferIntervalOldData = Db::name('statistic_project_ip_transfer_interval')->where('day', $day)->where('project', $project)->where('ip', $ip)->where('transfer', $transfer)->column('id', 'time');
                $statisticProjectIpTransferIntervalNewData = [];
                foreach ($statisticProjectIpTransferIntervalCount as $time => $count) {
                    if ($time < date('YmdHi', time() - 300)) {
                        continue;
                    }
                    $data = [
                        'day'           => $day,
                        'time'          => $time,
                        'project'       => $project,
                        'ip'            => $ip,
                        'transfer'      => $transfer,
                        'count'         => $count,
                        'cost'          => $statisticProjectIpTransferIntervalCost[$time] ?: 0,
                        'success_count' => $statisticProjectIpTransferIntervalSuccessCount[$time] ?: 0,
                        'error_count'   => $statisticProjectIpTransferIntervalErrorCount[$time] ?: 0,
                    ];
                    if (!empty($statisticProjectIpTransferIntervalOldData[$time])) {
                        Db::name('statistic_project_ip_transfer_interval')->where('id', $statisticProjectIpTransferIntervalOldData[$time])->update($data);
                    } else {
                        $statisticProjectIpTransferIntervalNewData[] = $data;
                    }
                }
                Db::name('statistic_project_ip_transfer_interval')->insertAll($statisticProjectIpTransferIntervalNewData);
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }
        }, $projectIpTransferList);
    }

    /**
     * 更新应用Client状态码统计数据
     *
     * @author HSK
     * @date 2022-06-20 10:02:19
     *
     * @param integer $day
     * @param string $project
     * @param string $ip
     *
     * @return void
     */
    protected function projectClientCodeStatistics(int $day, string $project, string $ip)
    {
        $ipTemp = str_replace(['::', ':'], '@', $ip);
        $projectIpCodeList = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':code:' . $day) ?: [];
        array_map(function ($code) use (&$project, &$day, &$ip, &$ipTemp) {
            try {
                // 耗时
                $statisticProjectIpCodeData['cost'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_cost:' . $code, $day) ?: 0;
                // 次数
                $statisticProjectIpCodeData['count'] = Redis::hGet('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_count:' . $code, $day) ?: 0;

                if ($statisticProjectTransferId = Db::name('statistic_project_ip_code')->where('day', $day)->where('project', $project)->where('ip', $ip)->where('code', $code)->value('id')) {
                    Db::name('statistic_project_ip_code')->where('id', $statisticProjectTransferId)->update($statisticProjectIpCodeData);
                } else {
                    $statisticProjectIpCodeData['day']     = $day;
                    $statisticProjectIpCodeData['project'] = $project;
                    $statisticProjectIpCodeData['ip']      = $ip;
                    $statisticProjectIpCodeData['code']    = $code;
                    Db::name('statistic_project_ip_code')->insert($statisticProjectIpCodeData);
                }


                // 每分钟统计数据，用于图表展示
                $statisticProjectIpCodeIntervalCount = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_count:' . $code . ':' . $day) ?: [];
                $statisticProjectIpCodeIntervalCost  = Redis::hGetAll('TransferStatistics:project:' . $project . ':client:' . $ipTemp . ':statistic:code_cost:' . $code . ':' . $day) ?: [];

                $statisticProjectIpCodeIntervalOldData = Db::name('statistic_project_ip_code_interval')->where('day', $day)->where('project', $project)->where('ip', $ip)->where('code', $code)->column('id', 'time');
                $statisticProjectIpCodeIntervalNewData = [];
                foreach ($statisticProjectIpCodeIntervalCount as $time => $count) {
                    if ($time < date('YmdHi', time() - 300)) {
                        continue;
                    }
                    $data = [
                        'day'     => $day,
                        'time'    => $time,
                        'project' => $project,
                        'ip'      => $ip,
                        'code'    => $code,
                        'count'   => $count,
                        'cost'    => $statisticProjectIpCodeIntervalCost[$time] ?: 0,
                    ];
                    if (!empty($statisticProjectIpCodeIntervalOldData[$time])) {
                        Db::name('statistic_project_ip_code_interval')->where('id', $statisticProjectIpCodeIntervalOldData[$time])->update($data);
                    } else {
                        $statisticProjectIpCodeIntervalNewData[] = $data;
                    }
                }
                Db::name('statistic_project_ip_code_interval')->insertAll($statisticProjectIpCodeIntervalNewData);
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
            }
        }, $projectIpCodeList);
    }
}
