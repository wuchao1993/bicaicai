<?php

namespace app\api\validate;

use think\Validate;

class Recharge extends Validate{

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'amount' => 'require',
        'payTypeId' => 'require',
        'rechargeDate' => 'require',
        'remark' => 'require',
        'rechargeAccountId' => 'require|number'
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'amount' => '充值金额不能为空',
        'payTypeId' => '充值类型不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'pay' => ['amount', 'payTypeId'],
        'specialAgentPay' => ['amount', 'payTypeId'],
        'friendPay' => ['amount', 'rechargeAccountId', 'remark', 'rechargeDate']
    ];

}