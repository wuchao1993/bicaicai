<?php

namespace app\admin\model;

use think\Model;

class PayBankRelation extends Model {

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pay_type_id' => '主键',
        'bank_id'     => '银行',
        'bank_code'   => '银行代码',
        'pay_type'    => '支付类型',
    ];

    public function getInfo($payTypeId, $bankId) {
        $condition['pay_type_id'] = $payTypeId;
        $condition['bank_id']     = $bankId;

        return $this->where($condition)->find();
    }

}