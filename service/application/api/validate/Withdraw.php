<?php

namespace app\api\validate;

use think\helper\Str;
use think\Validate;

class Withdraw extends Validate{

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'amount' => 'require|float|gt:0',
        'userBankId' => 'number',
        'fundsPassword' => 'require|regex:^\d{6}$'
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'userBankId' => '银行id类型错误',
        'amount' => '金额格式错误',
        'fundsPassword' => '资金密码为6个数字组成'
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'applyWithdraw' => ['userBankId', 'amount', 'fundsPassword'],
        'specialAgentWithdraw' => ['userBankId', 'amount', 'fundsPassword']
    ];


}