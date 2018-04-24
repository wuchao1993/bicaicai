<?php
/**
 * 后台推送验证器
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class PushChannel extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'id'              => 'require',
        'appName'         => 'require',
        'appKey'          => 'require',
        'page'            => 'number',
        'num'             => 'number',
        'platform'        => 'require',
        'appMasterSecret' => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'id'              => 'ID不能为空',
        'page'            => '页码格式不合法',
        'num'             => '分页数量不合法',
        'appName'         => 'appName不能为空',
        'appKey'          => 'appKey不能为空',
        'platform'        => '平台ID不能为空',
        'appMasterSecret' => 'appMasterSecret不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getPushChannelList'      => ['page', 'num'],
        'getPushChannelInfo'      => ['id'],
        'addPushChannel'          => ['appName', 'appKey', 'appMasterSecret', 'platform'],
        'editPushChannel'         => ['id', 'appName', 'appKey', 'appMasterSecret', 'platform'],
        'delPushChannel'          => ['id'],
    ];

}