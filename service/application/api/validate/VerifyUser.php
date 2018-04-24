<?php
/**
 * 验证器
 * @createTime 2017/4/4 14:31
 */

namespace app\api\validate;

use think\Validate;

class VerifyUser extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'loginPassword' => 'require|regex:^(?![^a-zA-Z]+$)(?!\D+$).{6,12}$',
        'fundPassword'  => 'require|regex:^[0-9]{6}$',
        'bankAccount'   => 'require|number',
        'bankUserName'  => 'require|chsAlpha',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'loginPassword' => '密码长度为6-12个字符之间，至少要有1个字母及数字',
        'fundPassword'  => '资金密码长度为6位数字',
        'bankAccount'   => '银行卡号不合法',
        'bankUserName'  => '银行账户名不合法',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'verifyBank'     => ['bankAccount', 'bankUserName'],
        'updatePassword' => ['loginPassword', 'fundPassword'],
    ];
}