<?php

namespace app\pay\logic;

use app\collect\logic\InPlayNow;
use think\Model;
use think\Config;
use think\Loader;
use think\Log;

class Notify extends Model{

    //充值通知
    public function handleOrder($params, $sign){
        //验签
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $signKey = $merchantInfo['sign_key'];
        $serverSign = build_request_sign($params, $signKey);
        //验签失败返回false
        if($serverSign != $sign) {
            Log::write('签名错误:' . print_r($sign, true));
            return false;
        }
        $orderInfo  = Loader::model('UserRechargeRecord')->getInfoByOrderId($params['outTradeNo']);
        //没有订单信息返回false
        if(!$orderInfo) {
            Log::write('没有订单记录:' . print_r($params, true));
            return false;
        }
        //订单状态为成功或已关闭返回ture
        if($orderInfo['urr_status'] == 1 || $orderInfo['urr_status'] == -1){
            return true;
        }
        //比较充值金额不同返回false
        if(bccomp($orderInfo['urr_amount'], $params['totalFee'], 3) !== 0) {
            Log::write('订单金额异常:' . print_r($params, true));
            return false;
        }
        $saveData['urr_trade_time']   = $params['payTime'];
        $saveData['urr_status']       = $params['tradeStatus'];
        $saveData['urr_confirm_time'] = current_datetime();
        $isFirst = Loader::model('UserRechargeRecord')->isFirst($orderInfo['user_id']);
        //是否为首充
        if(empty($isFirst)){
            $saveData['urr_is_first'] = Config::get('status.recharge_is_first')['yes'];
        }
        //开启事物
        $this->db()->startTrans();
        $condition['urr_id'] = $orderInfo['urr_id'];
        $condition['urr_status'] = 0;
        //修改信息
        $rechargeResult = Loader::model('UserRechargeRecord')->modify($condition, $saveData);
        if(empty($rechargeResult)){
            $this->db()->rollback();
            Log::write('修改订单状态失败:' . print_r($rechargeResult, true));
            return false;
        }
        //订单状态成功并修改信息成功
        if($params['tradeStatus'] == Config::get('status.recharge_status')['success'] && $rechargeResult) {
            //获取用户可用余额
            $userBeforeBalance = Loader::model('UserExtend', 'logic')->getBalance($orderInfo['user_id']);
            $extendResult = Loader::model('UserExtend', 'logic')->addRechargeAmount($orderInfo['user_id'], $orderInfo['urr_amount'], $orderInfo['urr_recharge_discount']);
            if(empty($extendResult)){
                $this->db()->rollback();
                return false;
            }
            $userAfterBalance = bcadd($userBeforeBalance, $orderInfo['urr_amount'], 3);
            $userAccountRecord['user_id']              = $orderInfo['user_id'];
            $userAccountRecord['uar_source_id']        = $orderInfo['urr_id'];
            $userAccountRecord['uar_source_type']      = SOURCE_TYPE_RECHARGE;
            $userAccountRecord['uar_transaction_type'] = ACCOUNT_TRANSACTION_TYPE_RECHARGE;
            $userAccountRecord['uar_amount']           = $orderInfo['urr_amount'];
            $userAccountRecord['uar_before_balance']   = $userBeforeBalance;
            $userAccountRecord['uar_after_balance']    = $userAfterBalance;
            $userAccountRecord['uar_remark']           = '在线充值';
            $userAccountRecord['uar_action_type']      = ACCOUNT_TRANSFER_IN;
            $accountRecordResult = Loader::model('UserAccountRecord')->save($userAccountRecord);
            if(bccomp($orderInfo['urr_recharge_discount'], 0, 3) > 0){
                $discountAccountRecord['user_id']                 = $orderInfo['user_id'];
                $discountAccountRecord['uar_source_id']           = $orderInfo['urr_id'];
                $discountAccountRecord['uar_source_type']         = SOURCE_TYPE_RECHARGE;
                $discountAccountRecord['uar_transaction_type']    = ACCOUNT_TRANSACTION_TYPE_DISCOUNT;
                $discountAccountRecord['uar_action_type']         = ACCOUNT_TRANSFER_IN;
                $discountAccountRecord['uar_amount']              = $orderInfo['urr_recharge_discount'];
                $discountAccountRecord['uar_before_balance']      = $userBeforeBalance;
                $discountAccountRecord['uar_after_balance']       = bcadd($userBeforeBalance, $orderInfo['urr_recharge_discount'], 3);
                $discountAccountRecord['uar_remark']              = '在线充值优惠';
                Loader::model('UserAccountRecord')->save($discountAccountRecord);
            }
            if(empty($accountRecordResult)){
                Log::write('添加用户账户明细失败:' . print_r($accountRecordResult, true));
                $this->db()->rollback();
                return false;
            }
            $this->db()->commit();
            Log::write('订单充值成功success');
            return true;
        }
    }

}