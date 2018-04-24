<?php

namespace app\api\validate;

use think\Validate;

class UserConfig extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'fundsPassword' => 'require|number|length:6',
        'id'            => 'require|number',
        'address'       => 'require',
        'cardNumber'    => 'require|number',
        'realName'      => 'require|chsAlpha'
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'fundsPassword'     => '资金密码长度为6位数字',
        'id'                => 'id不能为空',
        'address'           => '地址不能为空',
        'cardNumber'        => '银行卡号不能为空',
        'realName.chsAlpha' => '真实姓名不合法',
        'realName.require'  => '用户的真实姓名不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'bindRealInfo' => ['fundsPassword','realName'],
        'bindCard' => ['id', 'address', 'cardNumber']
    ];

}