<?php
/**
 * 验证器
 * @createTime 2017/5/29 14:36
 */

namespace app\api\validate;

use think\Validate;
use think\Config;

class Agent extends Validate
{

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'id' => 'require|number',
        'terminal' => 'checkTerminal',
        'status' => 'require|number|in:1,2,-1',
        'userName' => 'require|regex:^[a-zA-Z][a-zA-Z0-9_]{5,9}$',
        'password' => 'require|regex:^(?![^a-zA-Z]+$)(?!\D+$).{6,12}$',
        'captcha' => 'require|alphaNum',
        'email' => 'email',
        'qq' => 'number',
        'channel' => 'number',
        'startDate' => 'date',
        'endDate' => 'date',
        'page' => 'number|min:1',
        'count' => 'number|between:10,50',
        'type' => 'number|in:1,2',
        'userId' => 'require|number',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'type.number' => '开户类型必须是数字',
        'type.between' => '用户代理类型在1和2之间',
        'id.require' => '邀请码id不能为空',
        'id.number' => '邀请码id必须为数字',
        'status.require' => '邀请码使用状态不能为空',
        'status.number' => '邀请码使用状态必须为数字',
        'status.in' => '邀请码使用状态不在1,-1,2取值之间',
        'userName.require'   => '请输入用户名',
        'userName.regex'     => '用户名长度为6-10之间，只能由大小写字母开头、数字、下划线组成',
        'password.require'   => '请输入密码',
        'password.regex'     => '密码长度为6-12个字符之间，至少要有1个字母及数字',
        'captcha.alphaNum'   => '验证码格式不合法',
        'userId.require' => '下级用户id必须',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'generateInvitationCode' => ['type'],
        'editInvitationCode' => ['id', 'status'],
        'deleteInvitationCode' => ['id'],
        'applyAgent' => ['userName', 'password', 'captcha', 'mobile', 'email', 'contact', 'qq', 'terminal', 'channel'],
        'getAgentStatistics' => ['startDate', 'endDate'],
        'registerSubordinateAgent' => ['invitationCode', 'userName', 'password', 'captcha', 'terminal', 'mobile', 'email', 'contact', 'qq', 'deviceUniqueId'],
        'createSubordinate' => ['userName', 'type', 'rebate'],
        'getTeamList' => ['startDate', 'endDate', 'page', 'count'],
        'getSubordinateUserInfo' => ['userId']
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