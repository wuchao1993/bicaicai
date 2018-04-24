<?php

namespace app\common\logic;

use think\Loader;

class PayPlatformUserLevelRelation
{

    public function getPlatformIds($userLevelId)
    {
        $condition = [
            'user_level_id' => $userLevelId
        ];

        return Loader::model('PayPlatformUserLevelRelation')->where($condition)->column('pay_platform_id');
    }


}