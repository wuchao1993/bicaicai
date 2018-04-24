<?php
/**
 * 用户验证器
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class User extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'uid'                           => 'require',
        'realname'                      => 'require|chs',
        'password'                      => 'require',
        'confirmPassword'               => 'require',
        'captcha'                       => 'alphaNum',
        'page'                          => 'number',
        'num'                           => 'number',
        'mobile'                        => 'number|checkMobile',
        'email'                         => 'email',
        'status'                        => 'number',
        'ulName'                        => 'require',
        'ulDescription'                 => 'require',
        'id'                            => 'require|number',
        'proportion'                    => 'require|number',
        'ubId'                          => 'require',
        'ulId'                          => 'require',
        'everydayWithdrawCount'         => 'require',
        'repeatWithdrawTime'            => 'require',
        'everydayWithdrawFreeCount'     => 'require',
        'everydayWithdrawMaxAmount'     => 'require',
        'everytimeWithdrawMaxAmount'    => 'require',
        'everytimeWithdrawMinAmount'    => 'require',
        'withdrawFee'                   => 'require',
        'onlineDiscountStartAmount'     => 'require',
        'companyDiscountStartAmount'    => 'require',
        'artificialDiscountStartAmount' => 'require',
        'onlineDiscountPercentage'      => 'require',
        'companyDiscountPercentage'     => 'require',
        'artificialDiscountPercentage'  => 'require',
        'onlineRechargeMaxAmount'       => 'require',
        'companyRechargeMaxAmount'      => 'require',
        'artificialRechargeMaxAmount'   => 'require',
        'onlineRechargeMinAmount'       => 'require',
        'companyRechargeMinAmount'      => 'require',
        'artificialRechargeMinAmount'   => 'require',
        'onlineDiscountMaxAmount'       => 'require',
        'companyDiscountMaxAmount'      => 'require',
        'artificialDiscountMaxAmount'   => 'require',
        'rechargeTrafficMutiple'        => 'require',
        'discountTrafficMutiple'        => 'require',
        'relaxAmount'                   => 'require',
        'checkServiceCharge'            => 'require',
        'ulDefault'                     => 'require',
        'rebateList'                    => 'require',
        'bankList'                      => 'require',
        'ids'                           => 'require',
        'operate'                       => 'require',
        'userAgentCheckStatus'          => 'require',
        'username'                      => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'uid.require'                           => '用户ID不能为空',
        'realname.require'                      => '真实姓名不能为空',
        'realname.chs'                          => '真实姓名只能是汉字',
        'username.require'                      => '请输入用户名',
        'username.alphaDash'                    => '用户名长度为6-16之间，只能由字母、数字、下划线、破折号组成',
        'password.require'                      => '请输入密码',
        'password.regex'                        => '密码长度为5字符以上',
        'confirmPassword.require'               => '请输入确认密码',
        'confirmPassword.regex'                 => '确认密码长度为5字符以上',
        'captcha.alphaNum'                      => '验证码格式不合法',
        'page'                                  => '页码格式不合法',
        'num'                                   => '分页数量不合法',
        'mobile'                                => '手机格式错误',
        'email'                                 => '邮箱格式错误',
        'ulName.require'                        => '请输入用户层级名称',
        'ulDescription.require'                 => '请输入用户层级描述',
        'id.require'                            => '用户等级ID不能为空',
        'proportion.require'                    => '返水比例格式错误',
        'ubId'                                  => '银行id不能为空',
        'ulId.require'                          => '层级id不能为空',
        'everydayWithdrawCount.require'         => '每日提款次数不能为空',
        'repeatWithdrawTime.require'            => '重复出款时数不能为空',
        'everydayWithdrawFreeCount.require'     => '免手续费次数不能为空',
        'everydayWithdrawMaxAmount.require'     => '每日出款上限不能为空',
        'everytimeWithdrawMaxAmount.require'    => '每次出款上限不能为空',
        'everytimeWithdrawMinAmount.require'    => '每次出款下限不能为空',
        'withdrawFee.require'                   => '手续费扣点（百分比）不能为空',
        'onlineDiscountStartAmount.require'     => '线上存款优惠标准（优惠起始额度）不能为空',
        'companyDiscountStartAmount.require'    => '公司存款优惠标准不能为空',
        'artificialDiscountStartAmount.require' => '人工存款优惠标准不能为空',
        'onlineDiscountPercentage.require'      => '线上入款优惠百分比不能为空',
        'companyDiscountPercentage.require'     => '公司入款优惠百分比不能为空',
        'artificialDiscountPercentage.require'  => '人工存款优惠百分比不能为空',
        'onlineRechargeMaxAmount.require'       => '线上入款最大额度不能为空',
        'companyRechargeMaxAmount.require'      => '公司入款最大额度不能为空',
        'artificialRechargeMaxAmount.require'   => '人工存款最大额度不能为空',
        'onlineRechargeMinAmount.require'       => '线上入款最小额度不能为空',
        'companyRechargeMinAmount.require'      => '公司入款最小额度不能为空',
        'artificialRechargeMinAmount.require'   => '人工入款最小额度不能为空',
        'onlineDiscountMaxAmount.require'       => '线上入款优惠最大额度不能为空',
        'companyDiscountMaxAmount.require'      => '公司入款优惠最大额度不能为空',
        'artificialDiscountMaxAmount.require'   => '人工入款优惠最大额度不能为空',
        'rechargeTrafficMutiple.require'        => '入款打码倍数不能为空',
        'discountTrafficMutiple.require'        => '返水打码倍数不能为空',
        'relaxAmount.require'                   => '打码量归零额度不能为空',
        'checkServiceCharge.require'            => '达打码量提现行政费不能为空',
        'ulDefault.require'                     => '设置默认不能为空',
        'rebateList.require'                    => '返点信息不能为空',
        'bankList.require'                      => '银行信息不能为空',
        'ids.require'                           => '用户ID不能为空',
        'operate.require'                       => '是否锁定不能为空',
        'userAgentCheckStatus.require'          => '代理审核状态不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getUserList'                  => [
            'page',
            'num'
        ],
        'getUserInfo'                  => ['uid'],
        'addUser'                      => [
            'username',
            'realname',
            'password',
            'mobile',
            'email'
        ],
        'editUser'                     => ['uid'],
        'delUser'                      => ['uid'],
        'changeUserStatus'             => [
            'uid',
            'status'
        ],
        'changeAgentStatus'            => [
            'uid',
            'userAgentCheckStatus'
        ],
        'addUserLevel'                 => [
            'ulName',
            'ulDescription'
        ],
        'getUserRebateInfo'            => ['uid'],
        'getUserStatistics'            => ['uid'],
        'getUserAccountRecord'         => ['uid'],
        'getUserOrderList'             => ['uid'],
        'setSportRebate'               => [
            'id',
            'proportion'
        ],
        'getUserSportOrderList'        => ['uid'],
        'getUserBankInfo'              => ['ubId'],
        'getUserBankList'              => ['uid'],
        'getUserExtendInfo'            => ['username'],
        'editLevelPayConfig'           => [
            'ulId',
            'everydayWithdrawCount',
            'repeatWithdrawTime',
            'everydayWithdrawFreeCount',
            'everydayWithdrawMaxAmount',
            'everytimeWithdrawMaxAmount',
            'everytimeWithdrawMinAmount',
            'withdrawFee',
            'onlineDiscountStartAmount',
            'companyDiscountStartAmount',
            'artificialDiscountStartAmount',
            'onlineDiscountPercentage',
            'companyDiscountPercentage',
            'artificialDiscountPercentage',
            'onlineRechargeMaxAmount',
            'companyRechargeMaxAmount',
            'artificialRechargeMaxAmount',
            'onlineRechargeMinAmount',
            'companyRechargeMinAmount',
            'artificialRechargeMinAmount',
            'onlineDiscountMaxAmount',
            'companyDiscountMaxAmount',
            'artificialDiscountMaxAmount',
            'rechargeTrafficMutiple',
            'discountTrafficMutiple',
            'relaxAmount',
            'checkServiceCharge'
        ],
        'setDefaultUserLevel'          => [
            'ulId',
            'ulDefault'
        ],
        'editUserLevel'                => [
            'ulId',
            'ulName',
            'ulDescription'
        ],
        'addUserLevelAndEditPayConfig' => [
            'ulName',
            'ulDescription'
        ],
        'editUserRebate'               => [
            'uid',
            'rebateList'
        ],
        'delUserBank'                  => [
            'uid',
            'ubId'
        ],
        'editUserBank'                 => [
            'uid',
            'bankList'
        ],
        'addUserBank'                  => [
            'uid',
            'bankList'
        ],
        'editUserSelfLevel'            => [
            'uid',
            'ulId'
        ],
        'lockUserLevel'                => [
            'ids',
            'operate'
        ],
        'editUserRealName'            => [
            'uid'
        ],
    ];


    protected function checkMobile($value) {
        if($value) {
            if(preg_match('/1[34578]{1}\d{9}$/', $value) === 1) {
                return true;
            }

            return false;
        }
    }

    protected function checkProportion($value){
        if (is_int($value) || is_float($value)) {
            return true;
        }else{
            return false;
        }
    }
}