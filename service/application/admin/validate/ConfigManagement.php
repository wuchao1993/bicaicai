<?php
/**
 * 行为日志验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class ConfigManagement extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'id' 	   => 'require',
    	'page'	   => 'number',
    	'num'	   => 'number',
    	'name'	   => 'require',
    	'title'	   => 'require',
        'config'   => 'require',
        'ids'      => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'id.require'   	 	=> '配置ID不能为空',
    	'page'			 	=> '页码格式不合法',
    	'num'			 	=> '分页数量不合法',
    	'name'			 	=> '配置名称不能为空',
    	'title'			 	=> '配置说明不能为空',
        'config'            => '配置列表不能为空',
        'ids'               => '排序ID不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getConfigList' 	=> ['page', 'num'],
    	'getConfigInfo' 	=> ['id'],
    	'addConfig' 		=> ['name', 'title'],
    	'editConfig' 		=> ['id','name', 'title'],
    	'delConfig' 		=> ['id'],
        'editGroup'         => ['config'],
        'sortConfig'        => ['ids'],
    ];

}