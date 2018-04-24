<?php

namespace app\common\logic;

use think\Config;
use think\Loader;

class PayPlatform
{

    public function getListByUserLevelId($userLevelId, $rechargeTypeId)
    {
        $platformIds = Loader::model('PayPlatformUserLevelRelation', 'logic')->getPlatformIds($userLevelId);

        $condition = [
            'pp_id' => ['in', $platformIds],
            'pp_category_id' => $rechargeTypeId,
            'pp_status' => Config::get('status.pay_platform_status')['enable'],
        ];

        return Loader::model('PayPlatform')->where($condition)->select();
    }

}