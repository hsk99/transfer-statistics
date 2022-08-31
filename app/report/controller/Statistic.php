<?php

namespace app\report\controller;

use support\Redis;

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

            foreach ($transferList as $item) {
                try {
                    $itemArray = json_decode($item, true);
                    if (
                        !isset($itemArray['project']) ||
                        !isset($itemArray['ip']) ||
                        !isset($itemArray['transfer']) ||
                        !isset($itemArray['costTime']) ||
                        !isset($itemArray['success']) ||
                        !isset($itemArray['time']) ||
                        !isset($itemArray['code']) ||
                        !isset($itemArray['details'])
                    ) {
                        continue;
                    }
                    Redis::rPush('TransferCache', $item);
                } catch (\Throwable $th) {
                    \Hsk99\WebmanException\RunException::report($th);
                }
            }
            
            return api([], 200, 'success');
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
            return api([], 400, 'error');
        }
    }
}
