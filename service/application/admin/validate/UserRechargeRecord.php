<?php
/**
 * 用户入款验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class UserRechargeRecord extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'uid'      => 'require',
        'page'     => 'number',
        'num'      => 'number',
        'username' => 'require',
        'amount'   => 'require|gt:0',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'uid.require'      => '用户ID不能为空',
        'page'             => '页码格式不合法',
        'num'              => '分页数量不合法',
        'username.require' => '用户名不能为空',
        'amount.require'   => '金额不能为空'
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getSystemList' => [
            'page',
            'num'
        ],
        'addSystem'     => [
            'username'
        ],
    ];

}