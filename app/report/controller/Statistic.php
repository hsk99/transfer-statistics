<?php

namespace app\report\controller;

class Statistic
{
    /**
     * 上报数据
     *
     * @author HSK
     * @date 2022-06-15 10:00:47
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function transfer(\support\Request $request)
    {
        try {
            $transfer = request()->input('transfer');
            if (empty($transfer)) {
                return api([], 400, 'error');
            }

            $transferList = explode("\n", $transfer);
            $transferList = array_filter($transferList);
            array_map(function ($item) {
                try {
                    \Webman\RedisQueue\Client::connection('statistic')->send('statistic', json_decode($item, true));
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }
            }, $transferList);

            return api([], 200, 'success');
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
            return api([], 400, 'error');
        }
    }
}
