<?php
/**
 * 用户统计资料表
 */

namespace app\admin\model;

use think\Model;

class UserExtend extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'user_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'user_id'                   => '主键',
        'ue_account_balance'        => '账户余额',
        'ue_discount_amount'        => '优惠总额',
        'ue_recharge_amount'        => '充值总额',
        'ue_withdraw_amount'        => '提现总额',
        'ue_mg_balance'             => 'mg平台余额',
        'ue_ct_balance'             => 'ct平台余额',
        'ue_bbin_balance'           => 'bbin平台余额',
        'ue_ag_balance'             => 'ag平台余额',
        'ue_bg_balance'             => 'bg平台余额',
        'ue_og_balance'             => 'og平台余额',
        'ue_xtd_balance'            => 'xtd平台余额',
        'ue_valid_bet_sports'       => '体育有效投注',
        'ue_valid_bet_video'        => '视频有效投注',
        'ue_valid_bet_lottery'      => '',
        'ue_valid_bet_electronic'   => '',
        'ue_bet_traffic_sports'     => '',
        'ue_bet_traffic_video'      => '',
        'ue_bet_traffic_lottery'    => '彩票打码量',
        'ue_bet_traffic_electronic' => '',
        'ue_withdraw_bet_traffic'   => '提现需要投注量',
        'ue_withdraw_count'         => '提现次数',
        'ue_recharge_count'         => '充值次数',
        'ue_login_count'            => '登陆次数',
        'ue_recharge_max_amount'    => '充值最大金额',
    ];

    public function getBalance($userId) {
        $condition            = [];
        $condition['user_id'] = $userId;

        return $this->where($condition)->value('ue_account_balance');
    }


    public function addMoney($userId, $amount) {
        $condition            = [];
        $condition['user_id'] = $userId;

        return $this->where($condition)->setInc('ue_account_balance', $amount);
    }


    public function addRechargeAmount($userId, $amount, $rechargeDiscount) {
        $totalAmount = bcadd($amount, $rechargeDiscount, 3);
        $user_extend = [
            'ue_account_balance' => [
                'exp',
                'ue_account_balance+' . $totalAmount,
            ],
            'ue_discount_amount' => [
                'exp',
                'ue_discount_amount+' . $rechargeDiscount,
            ],
            'ue_recharge_amount' => [
                'exp',
                'ue_recharge_amount+' . $amount,
            ],
            'ue_recharge_count'  => [
                'exp',
                'ue_recharge_count+1',
            ],
        ];

        $condition            = [];
        $condition['user_id'] = $userId;

        return $this->where($condition)->update($user_extend);
    }

    public function getInfosByRecharge($userIds){

        $condition = [];
        $condition['user_id'] = array('IN', $userIds);

        $fields = "user_id,ue_account_balance,ue_discount_amount,ue_recharge_amount,ue_recharge_count";

        return $this->where($condition)->field($fields)->select();
    }

}