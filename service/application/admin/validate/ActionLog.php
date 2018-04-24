<?php
/**
 * 行为日志验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class ActionLog extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'id' 	   => 'require',
    	'page'	   => 'number',
    	'num'	   => 'number',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'id.require'   	 	=> '行为日志ID不能为空',
    	'page'			 	=> '页码格式不合法',
    	'num'			 	=> '分页数量不合法',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getActionLogList' 	=> ['page', 'num'],
    	'getActionLogInfo' 	=> ['id'],
    	'delActionLog' 		=> ['id'],
    ];

}