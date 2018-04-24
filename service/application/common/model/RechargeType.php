<?php

namespace app\common\model;

use think\model;

class RechargeType extends model{

    /**
     * 定义主键
     * @var int
     */
    protected $pk = 'recharge_type_id';

    public function getRechargeTypeIdCodeMap(){
        $condition['recharge_type_code'] = ['neq', ''];
        return $this->where($condition)->column('recharge_type_id, recharge_type_code, recharge_type_name', 'recharge_type_code');
    }
}