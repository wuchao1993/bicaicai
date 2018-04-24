<?php
/**
 * 后台推送验证器
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class PushMessage extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'id'         => 'require',
        'title'      => 'require',
        'type'       => 'require',
        'page'       => 'number',
        'num'        => 'number',
        'content'    => 'require',
        'addType'    => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'id'         => 'ID不能为空',
        'page'       => '页码格式不合法',
        'num'        => '分页数量不合法',
        'type'       => '类型不能为空',
        'addType'    => '添加类型不能为空',
        'title'      => '标题不能为空',
        'content'    => '推送内容不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getPushMessageList'      => ['page', 'num'],
        'getPushMessageInfo'      => ['id'],
        'addPushMessage'          => ['title', 'type', 'content', 'addType'],
        'editPushMessage'         => ['id', 'title', 'type', 'content', 'addType'],
        'delPushMessage'          => ['id'],
    ];

}