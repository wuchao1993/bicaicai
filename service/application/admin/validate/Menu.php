<?php
/**
 * 菜单验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class Menu extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'id' 	   => 'require',
    	'page'	   => 'number',
    	'num'	   => 'number',
    	'title'	   => 'require',
    	'routeName'=> 'require',
    	'url'	   => 'require',
        'ids'      => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'id.require'   	 	=> '菜单ID不能为空',
    	'page'			 	=> '页码格式不合法',
    	'num'			 	=> '分页数量不合法',
        'ids'               => '排序ID不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getMenuList' 	=> ['page', 'num'],
    	'getMenuInfo' 	=> ['id'],
    	'addMenu' 		=> ['title','routeName','url'],
    	'editMenu' 		=> ['id','title','routeName','url'],
    	'delMenu' 		=> ['id'],
        'sortMenu'      => ['ids'],
    ];

}