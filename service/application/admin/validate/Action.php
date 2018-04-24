<?php
/**
 * 用户行为验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class Action extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'id' 	   => 'require',
    	'groupId'  => 'number',
    	'page'	   => 'number',
    	'num'	   => 'number',
    	'name'	   => 'require',
    	'title'	   => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'id.require'   	 	=> '用户行为ID不能为空',
    	'page'			 	=> '页码格式不合法',
    	'num'			 	=> '分页数量不合法',
    	'name'				=> '行为唯一标识不能为空',
    	'title'				=> '行为说明不能为空'
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getActionList' 	=> ['page', 'num', 'groupId'],
    	'getActionInfo' 	=> ['id'],
    	'addAction' 		=> ['name', 'title'],
    	'editAction' 		=> ['id', 'name', 'title'],
    	'delAction' 		=> ['id'],
    	'changeActionStatus' => ['id', 'status'],
    ];

}