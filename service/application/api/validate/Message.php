<?php
/**
 * 验证器
 * @createTime 2017/5/29 14:36
 */

namespace app\api\validate;

use think\Validate;

class Message extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'messageId'             => 'number|require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'messageId.number'             => '站内信信息id必须是数字',
        'messageId.require'            => '站内信信息id不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getMessageInfo'     => ['messageId'],
        'deleteMessageInfo'  => ['messageId'],

    ];
}