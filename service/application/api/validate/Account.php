<?php
/**
 * 验证器
 * @createTime 2017/5/29 14:36
 */

namespace app\api\validate;

use think\Config;
use think\Validate;

class Account extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'typeId'             => 'number|egt:0',
        'sportId'            => 'require|number|gt:0',
        'page'               => 'number|egt:0',
        'orderNo'            => 'number',
        'startTime'          => 'date',
        'endTime'            => 'date',
        'status'             => 'in:wait,success,fail,close',
        'oldPassword'        => 'require|regex:^(?![^a-zA-Z]+$)(?!\D+$).{6,12}$',
        'newPassword'        => 'require|regex:^(?![^a-zA-Z]+$)(?!\D+$).{6,12}$',
        'newPasswordConfirm' => 'require|regex:^(?![^a-zA-Z]+$)(?!\D+$).{6,12}$',
        'oldFundsPassword'   => 'require|regex:^[0-9]{6}$',
        'newFundsPassword'   => 'require|regex:^[0-9]{6}$',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'typeId'             => '类型id不合法',
        'sportId'            => '请传入球类id',
        'page'               => '页码不合法',
        'oldPassword'        => '密码长度为6-12个字符之间，至少要有1个字母及数字',
        'newPassword'        => '密码长度为6-12个字符之间，至少要有1个字母及数字',
        'newPasswordConfirm' => '密码长度为6-12个字符之间，至少要有1个字母及数字',
        'oldFundsPassword'   => '资金密码长度为6位数字',
        'newFundsPassword'   => '资金密码长度为6位数字',
        'status.in'          => '订单状态不在取值wait,success,fail,close范围',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'betLimitSetting' => ['sportId'],
        'detailRecords'   => [
            'typeId',
            'page',
        ],
        'rechargeRecords' => [
            'typeId',
            'page',
        ],
        'pcRechargeRecords' => [
            'typeId',
            'page',
            'orderNo',
            'startTime',
            'endTime',
            'status',
        ],
        'withdrawRecords' => [
            'typeId',
            'page',
        ],
        'noticeList'      => [
            'typeId',
            'page',
        ],
        'changePassword'  => [
            'oldPassword',
            'newPassword',
            'newPasswordConfirm',
        ],
        'changeFundsPassword' => [
            'oldFundsPassword',
            'newFundsPassword'
        ],
    ];
}