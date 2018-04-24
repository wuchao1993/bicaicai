<?php
/**
 * 用户额外相关业务逻辑
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;

class UserExtend extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取用户信息
     * @param $uid
     * @return array
     */
    public function getInfo($uid) {
        $condition = ['user_id' => $uid];
        $info      = Loader::model('UserExtend')->where($condition)->find();

        return $info;
    }

    /**
     * 获取用户余额
     * @param $userId
     * @return array
     */
    public function getBalance($userId) {
        $condition            = array();
        $condition['user_id'] = $userId;

        $info = Loader::model('UserExtend')->where($condition)->field('ue_account_balance')->find();

        return $info['ue_account_balance'];
    }

    /**
     * 添加入款信息
     * @param $userId
     * @param $amount
     * @param $discount
     * @return boolean
     */
    public function addRechargeAmount($userId, $amount, $discount, $isFormal = true) {
        $total_amount                      = bcadd($amount, $discount, 3);
        $user_extend                       = array();
        $user_extend['ue_account_balance'] = array(
            'exp',
            'ue_account_balance+' . $total_amount
        );
        $user_extend['ue_discount_amount'] = array(
            'exp',
            'ue_discount_amount+' . $discount
        );
        if($isFormal){
            $user_extend['ue_recharge_amount'] = array(
                'exp',
                'ue_recharge_amount+' . $amount
            );
            $user_extend['ue_recharge_count']  = array(
                'exp',
                'ue_recharge_count+1'
            );
        }

        return Loader::model('UserExtend')->save($user_extend, ['user_id' => $userId]);
    }

    /**
     * 添加出款信息
     * @param $userId
     * @param $amount
     * @param $feeAmount
     * @return boolean
     */
    public function addWithdrawAmount($userId, $amount, $feeAmount = 0) {
        $total_amount                      = bcadd($amount, $feeAmount, 3);
        $user_extend                       = array();
        $user_extend['ue_withdraw_amount'] = array(
            'exp',
            'ue_withdraw_amount+' . $total_amount
        );
        $user_extend['ue_account_balance'] = array(
            'exp',
            'ue_account_balance-' . $total_amount
        );
        $user_extend['ue_withdraw_count']  = array(
            'exp',
            'ue_withdraw_count+1'
        );

        return Loader::model('UserExtend')->save($user_extend, ['user_id' => $userId]);
    }

    /**
     * 出款信息
     * 修改issue https://gitlab.kosun.net/sports/service/issues/496
     * 人工出款不计入出款总额 by zoro
     * @param $userId
     * @param $amount
     * @param $feeAmount
     * @return boolean
     */
    public function NewAddWithdrawAmount($userId, $amount, $feeAmount = 0) {
        $total_amount                      = bcadd($amount, $feeAmount, 3);
        $user_extend                       = array();
        $user_extend['ue_account_balance'] = array(
            'exp',
            'ue_account_balance-' . $total_amount
        );
        return Loader::model('UserExtend')->save($user_extend, ['user_id' => $userId]);
    }

    /**
     * 提现取消
     * @param $sourceId
     * @return boolean
     */
    public function withdrawCancel($sourceId) {
        $uwInfo = Loader::model('UserWithdrawRecord')->where(array(
            'uwr_id'     => $sourceId,
            'uwr_status' => Config::get('status.withdraw_status')['lock']
        ))->find();

        if(empty($uwInfo)) {
            return false;
        }

        $userId         = $uwInfo['user_id'];
        $apply_amount    = $uwInfo['uwr_apply_amount'];
        $handling_charge = $uwInfo['uwr_handling_charge'];
        $total_amount    = bcadd($apply_amount, $handling_charge, 2);
        $ueModel         = Loader::model('UserExtend');
        $ueInfo          = $ueModel->where(array('user_id' => $userId))->find();
        $balance         = $ueInfo['ue_account_balance'];

        $this->startTrans();
        $ueModel->id                 = $userId;
        $ueModel->ue_account_balance = array(
            'exp',
            'ue_account_balance+' . $total_amount
        );
        $ueModel->ue_withdraw_amount = array(
            'exp',
            'ue_withdraw_amount-' . $total_amount
        );
        $ueModel->ue_withdraw_count  = array(
            'exp',
            'ue_withdraw_count-1'
        );
        $ueModel->save();

        $model                       = Loader::model('UserAccountRecord');
        $model->user_id              = $userId;
        $model->uar_source_id        = $sourceId;
        $model->uar_source_type      = Config::get('status.user_account_record_source_type')['withdraw'];
        $model->uar_transaction_type = Config::get('status.account_record_transaction_type')['withdraw_cancel'];
        $model->uar_action_type      = Config::get('status.account_record_action_type')['deposit'];
        $model->uar_amount           = $total_amount;
        $model->uar_before_balance   = $balance;
        $model->uar_after_balance    = bcadd($balance, $total_amount, 2);

        $model->uar_remark = '提现取消';
        $model->add();

        $result = Loader::model('UserWithdrawRecord')->where(array(
            'uwr_id'     => $sourceId,
            'uwr_status' => Config::get('status.withdraw_status')['lock']
        ))->save(array(
            'uwr_status'      => Config::get('status.withdraw_status')['cancel'],
            'uwr_operator_id' => MEMBER_ID
        ));
        if(!empty($result)) {
            $this->commit();

            return true;
        } else {
            $this->rollback();

            return false;
        }
    }
}