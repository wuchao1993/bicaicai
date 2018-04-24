<?php
/**
 * 权限组验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class AuthGroup extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'id' 	   => 'require',
    	'groupId'  => 'require|number',
    	'uid'  	   => 'require|number',
    	'page'	   => 'number',
    	'num'	   => 'number',
        'title'    => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'id.require'   	 	=> '权限组ID不能为空',
    	'groupId.require'  	=> '权限组ID不能为空',
    	'uid.require'  		=> '管理员ID不能为空',
    	'page'			 	=> '页码格式不合法',
    	'num'			 	=> '分页数量不合法',
        'title.require'     => '标题不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getAuthGroupList' 	=> ['page', 'num'],
    	'getAuthGroupInfo' 	=> ['id'],
    	'addAuthGroup' 		=> ['title'],
    	'editAuthGroup' 	=> ['id','title'],
    	'delAuthGroup' 		=> ['id'],
    	'changeAuthGroupStatus' => ['id', 'status'],
    	'getUserList' 		=> ['groupId'],
    	'addUser' 			=> ['groupId','uid'],
    	'delUser' 			=> ['groupId','uid'],
    ];

}