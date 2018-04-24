<?php
/**
 * 用户信息扩展表
 * @createTime 2017/5/10 14:17
 */

namespace app\common\logic;

use think\Loader;
use think\Model;

class UserExtend extends Model {

    /**
     * 扣除用户余额
     * @param $uid 用户id
     * @param $amount 金额
     * @return bool
     */
    public function balanceDeduct($uid, $amount) {
        $update['ue_account_balance'] = [
            'exp',
            'ue_account_balance-' . $amount
        ];
        $ret = Loader::model('UserExtend')->where('user_id', $uid)->update($update);
        return $ret === false ? false : true;
    }

    /**
     * 增加用户余额
     * @param $uid 用户id
     * @param $amount 金额
     * @return bool
     */
    public function balanceAdd($uid, $amount) {
        $update['ue_account_balance'] = [
            'exp',
            'ue_account_balance+' . $amount
        ];
        $ret = Loader::model('UserExtend')->where('user_id', $uid)->update($update);
        return $ret === false ? false : true;
    }


    public function getInfo($userId){
        $condition = [
            'user_id' => $userId,
        ];

        return Loader::model('UserExtend')->where($condition)->find();
    }


    public function getBalance($userId){
        $condition = [
            'user_id' => $userId,
        ];

        return Loader::model('UserExtend')->where($condition)->value('ue_account_balance');
    }


    public function addWithdrawAmount($userId, $amount, $withdrawFee=0){
        $totalAmount = bcadd($amount, $withdrawFee, 3);
        $userExtend = array();
        $userExtend['ue_withdraw_amount'] = array('exp', 'ue_withdraw_amount+'.$totalAmount);
        $userExtend['ue_account_balance'] = array('exp', 'ue_account_balance-'.$totalAmount);
        $userExtend['ue_withdraw_count']  = array('exp', 'ue_withdraw_count+1');

        $userExtend = [
            'ue_withdraw_amount' => ['exp', 'ue_withdraw_amount+'.$totalAmount],
            'ue_account_balance' => ['exp', 'ue_account_balance-'.$totalAmount],
            'ue_withdraw_count' => ['exp', 'ue_withdraw_count+1'],
        ];

        $condition['user_id'] = $userId;

        return Loader::model('UserExtend')->save($userExtend, $condition);
    }

    /**
     * 添加入款信息
     * @param $userId
     * @param $amount
     * @param $discount
     * @return boolean
     */
    public function addRechargeAmount($userId, $amount, $discount, $isFormal = true)
    {
        $totalAmount = bcadd($amount, $discount, 3);
        $userExtend = array();
        $userExtend['ue_account_balance'] = array(
            'exp',
            'ue_account_balance+' . $totalAmount
        );
        $userExtend['ue_discount_amount'] = array(
            'exp',
            'ue_discount_amount+' . $discount
        );
        if ($isFormal) {
            $userExtend['ue_recharge_amount'] = array(
                'exp',
                'ue_recharge_amount+' . $amount
            );
            $userExtend['ue_recharge_count'] = array(
                'exp',
                'ue_recharge_count+1'
            );
        }
        return Loader::model('UserExtend')->save($userExtend, ['user_id' => $userId]);
    }

}