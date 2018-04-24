<?php

namespace app\common\model;

use think\Model;

class PayCenterAccountUserLevelRelation extends Model{

    public function getPayCenterChannelIds($userLevelId)
    {
        $condition = [
            'user_level_id' => $userLevelId
        ];

        return $this->where($condition)->column('channel_merchant_id');
    }

}