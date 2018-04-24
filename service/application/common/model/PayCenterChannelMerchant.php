<?php

namespace app\common\model;

use think\Model;
use think\Loader;
use think\Config;

class PayCenterChannelMerchant extends Model{

    public $pk = 'channel_merchant_id';

    public function getChannelListByUserLevelId($userLevelId, $rechargeTypeId)
    {
        $channelIds = Loader::model('common/PayCenterAccountUserLevelRelation')->getPayCenterChannelIds($userLevelId);
        $condition = [
            'channel_merchant_id' => ['in', $channelIds],
            'pay_type_id' => $rechargeTypeId,
            'status' => Config::get('status.pay_platform_status')['enable'],
        ];

        $result = $this->where($condition)->select();

        return $result ? collection($result)->toArray() : $result;
    }

}