<?php

namespace app\common\model;

use think\Model;

class PayPlatform extends Model
{

    public function payPlatformUserLevelRelation()
    {
        return $this->hasOne('PayPlatformUserLevelRelation')->field('user_level_id');
    }


    public function addStatistics($ppId, $rechargeAmount){
        $condition = [
            'pp_id' => $ppId,
        ];

        $data = [
            'pp_recharge_amount' => [
                'exp',
                'pp_recharge_amount+' . $rechargeAmount,
            ],
            'pp_recharge_count'  => [
                'exp',
                'pp_recharge_count+1',
            ],
        ];

        return $this->where($condition)->update($data);
    }

}