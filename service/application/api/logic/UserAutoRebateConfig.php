<?php
namespace app\api\logic;

use think\Loader;

class UserAutoRebateConfig{

    public function addUserRebateConfig($rebateConfig, $userId, $userPid)
    {
        $rebateConfig = array_filter($rebateConfig);
        $rebate_data = array();
        foreach ($rebateConfig as $categoryId => $rebate) {
            $temp = array();
            $temp['user_id'] = $userId;
            $temp['lottery_category_id'] = $categoryId;
            $temp['user_rebate'] = $rebate;
            $temp['user_pid'] = $userPid;
            $rebate_data[] = $temp;
        }

        return Loader::model('UserAutoRebateConfig')->saveAll($rebate_data);
    }

}