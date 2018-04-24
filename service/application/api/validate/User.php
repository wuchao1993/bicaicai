<?php
/**
 * 验证器
 * @createTime 2017/4/4 14:31
 */

namespace app\api\validate;

use think\Config;
use think\Validate;

class User extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'userName' => 'require|regex:[A-Za-z0-9_]{6,20}',
        'password' => 'require|regex:^(?![^a-zA-Z]+$)(?!\D+$).{6,32}$',
        'terminal' => 'checkTerminal',
        'captcha'  => 'alphaNum',
        'uid'      => 'require|number|gt:0',
        'channel'  => '',
        'openid'   => 'require',
        'type'     => 'require|number',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'userName.require'   => '请输入用户名',
        'userName.regex'     => '用户名长度为6-20之间，只能由字母、数字、下划线组成',
        'password.require'   => '请输入密码',
        'password.regex'     => '密码长度为6-32个字符之间，至少要有1个字母及数字',
        'captcha.alphaNum'   => '验证码格式不合法',
        'openid.require'     => '第三方登录TOKEN不能为空',
        'type.require'       => '第三方登录类型不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'signIn'             => ['userName' => 'require', 'password' => 'require', 'terminal'],
        'signUp'             => ['userName' => 'require|regex:[A-Za-z0-9_]{6,16}', 'password', 'terminal', 'captcha'],
        'guestSignUp'        => ['terminal'],
        'specialAgentSignIn' => ['userName', 'password', 'terminal'],
        'specialAgentSignUp' => ['userName', 'password'],
        'check'              => ['userName'],
        'getInfo'            => ['uid'],
        'thirdSignIn'        => ['openid', 'type'],
        'thirdSignUpImprove' => ['openid', 'type', 'userName', 'password'],
    ];

    /**
     * 判断登录注册的平台类型
     * @param $value
     * @return string
     */
    public function checkTerminal($value) {
        if (!Config::get('status.reg_terminal')[$value]) {
            return '登录平台不合法';
        }
        return true;
    }
}