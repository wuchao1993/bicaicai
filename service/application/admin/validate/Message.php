<?php
/**
 * 站内信验证器
 */

namespace app\admin\validate;

use think\Validate;

class Message extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'userIds' 	        => 'require',
    	'title'         => 'require',
    	'content'       => 'require',
        'messageId'     => 'require',
        ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'userIds'           => '会员id不能为空',
    	'title'             => '站内信标题不能为空',
    	'content'           => '站内信内容不能为空',
        'messageId'         => '站内信息id不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getMessage' 	     => ['messageId'],
        'sendMessage'        => ['userIds','title','content'],
        'deleteMessage' 	 => ['messageId'],

    ];

}