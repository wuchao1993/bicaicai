<?php
/**
 * 管理员验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class Member extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'uid' 	   => 'require',
        'nickname' => 'require|alphaDash',
        'password' => 'require',
    	'confirmPassword' => 'require',
        'captcha'  => 'alphaNum',
    	'page'	   => 'number',
    	'num'	   => 'number',
    	'mobile'   => 'number',
    	'email'	   => 'email',
    	'status'   => 'number',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'uid.require'   	 => '管理员ID不能为空',
        'nickname.require'   => '请输入用户名',
        'nickname.alphaDash' => '用户名长度为6-16之间，只能由字母、数字、下划线、破折号组成',
        'password.require'   => '请输入密码',
        'password.regex'     => '密码长度为5字符以上',
    	'confirmPassword.require'   => '请输入确认密码',
    	'confirmPassword.regex'     => '确认密码长度为5字符以上',
        'captcha.alphaNum'   => '验证码格式不合法',
    	'page'				 => '页码格式不合法',
    	'num'				 => '分页数量不合法',
    	'mobile'   			 => '手机格式错误',
    	'email'				 => '邮箱格式错误',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'memberLogin' 	=> ['nickname', 'password', 'captcha'],
    	'memberLogout' 	=> ['uid'],
        'getMemberList' => ['page', 'num'],
    	'getMemberInfo' => ['uid'],
    	'addMember' 	=> ['nickname', 'confirmPassword', 'mobile', 'email'],
    	'editMember' 	=> ['uid','nickname', 'mobile', 'email'],
    	'delMember' 	=> ['uid'],
    	'changeMemberStatus' => ['uid', 'status'],
    	'getMenuList' 	=> ['uid'],
    	'editMemberAuthGroup' => ['uid'],
        'changePassword' 	=> ['password', 'confirmPassword'],
    ];

}