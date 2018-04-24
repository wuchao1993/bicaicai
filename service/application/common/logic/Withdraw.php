<?php
 
namespace app\common\logic;

use think\Config;
use think\Loader;
use think\Db;

class Withdraw
{

    public $errorcode = EC_SUCCESS;

    public function withdraw($userBankId, $amount, $fundsPassword)
    {
        $userId = USER_ID;
        if (!$userId) {
            $this->errorcode = EC_USER_JWT_EXP_ERROR;
            return;
        }

        if (is_test_user()) {
            $this->errorcode = EC_IS_TRY_ACCOUNT;
            return;
        }

        if($amount <= 0){
            $this->errorcode = EC_WITHDRAW_AMOUNT_MIN_LIMIT;
            return;
        }

        $userBankInfo = Loader::model('UserBankRelation', 'logic')->getInfo($userBankId);
        if ($userBankInfo['user_id'] != $userId) {
            $this->errorcode = EC_USER_BANK_ID_ERROR;
            return;
        }

        $userInfo = Loader::model('User', 'logic')->getInfo($userId);
        $fundsSalt = $userInfo['user_funds_salt'];
        $enPassword = encrypt_password($fundsPassword, $fundsSalt);
        if ($enPassword != $userInfo['user_funds_password']) {
            $this->errorcode = EC_FUNDS_PASSWORD_ERROR;
            return;
        }

        //验证余额
        $userExtendInfo = Loader::model('UserExtend', 'logic')->getInfo($userId);
        $userBalance = $userExtendInfo['ue_account_balance'];

        if($userBalance < $amount){
            return show_response(EC_ORDER_BALANCE_NOT_ENOUGH, '余额不足！');
        }

        //单次提现额度限制
        $userPayConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($userInfo['ul_id']);
        $withdrawMinAmount = $userPayConfig['pc_everytime_withdraw_min_amount'];
        if ($amount < $withdrawMinAmount) {
            return show_response(EC_WITHDRAW_AMOUNT_MIN_LIMIT, "当前用户最低可提现金额为{$withdrawMinAmount}元");
        }

        $withdrawMaxAmount = $userPayConfig['pc_everytime_withdraw_max_amount'];
        if ($amount > $withdrawMaxAmount) {
            return show_response(EC_WITHDRAW_AMOUNT_MAX_LIMIT, "当前用户最高可提现金额为{$withdrawMaxAmount}元");
        }

        $checkStartDate = date('Y-m-d H:i:s', time() - ($userPayConfig['pc_repeat_withdraw_time'] * 3600));
        $withdrawRecords = Loader::model('UserWithdrawRecord', 'logic')->getLatelyRecords($userId, $checkStartDate);

        //当日提款次数限制
        if (count($withdrawRecords) >= $userPayConfig['pc_everyday_withdraw_count']) {
            $this->errorcode = EC_WITHDRAW_COUNT_PERIOD_LIMIT;
            return;
        }

        $periodWithdrawTotalAmount = array_sum(extract_array($withdrawRecords, 'uwr_apply_amount'));

        //当日提款额度限制
        if (bcadd($periodWithdrawTotalAmount, $amount) > $userPayConfig['pc_everyday_withdraw_max_amount']) {
            $this->errorcode = EC_WITHDRAW_AMOUNT_PERIOD_LIMIT;
            return;
        }

        //超出每日免费提现次数限制,扣除手续费
        if (count($withdrawRecords) >= $userPayConfig['pc_everyday_withdraw_free_count']) {
            $withdrawFee = bcmul(bcmul($amount, $userPayConfig['pc_withdraw_fee']), 0.01);
        } else {
            $withdrawFee = 0;
        }

        $notAllowWithdrawAmount = Loader::model('User', 'logic')->getNotAllowWithdrawAmount($userId);
        $maxWithdrawAmount = bcsub($userBalance, $notAllowWithdrawAmount, 2);
        $maxWithdrawAmount = $maxWithdrawAmount < 0 ? 0 : $maxWithdrawAmount;
        if (bcadd($amount, $withdrawFee) > $maxWithdrawAmount) {
            return show_response(EC_USER_WITHDRAW_LIMIT, "当前可提现余额为{$maxWithdrawAmount}元");
        }

        $withdrawId = $this->_saveWithdrawRecord($userId, $amount, $withdrawFee, $userBankId);
        if ($withdrawId == false) {
            return show_response(EC_ORDER_BALANCE_NOT_ENOUGH, '余额不足！');
        }
    }


    private function _saveWithdrawRecord($userId, $amount, $withdrawFee, $userBankId){
        Db::startTrans();
        $beforeUserBalance = Loader::model('UserExtend', 'logic')->getBalance($userId);

        $result = Loader::model('UserExtend', 'logic')->addWithdrawAmount($userId, $amount, $withdrawFee);
        if(empty($result)){
            Db::rollback();
            return false;
        }
        $afterUserBalance = Loader::model('UserExtend', 'logic')->getBalance($userId);
        if($afterUserBalance < 0){
            Db::rollback();
            return false;
        }

        $withdrawRecord = [];
        $withdrawRecord['user_id']                = $userId;
        $withdrawRecord['ub_id']                  = $userBankId;
        $withdrawRecord['uwr_no']                 = generate_order_number();
        $withdrawRecord['uwr_type']               = Config::get('status.user_withdraw_type')['online'];
        $withdrawRecord['uwr_apply_amount']       = $amount;
        $withdrawRecord['uwr_handling_charge']    = $withdrawFee ? $withdrawFee : 0;
        $withdrawRecord['uwr_real_amount']        = bcadd($amount, $withdrawFee, 2);
        $withdrawRecord['uwr_account_balance']    = $afterUserBalance;
        $withdrawRecord['uwr_status']             = Config::get('status.withdraw_status')['submit'];
        $withdrawRecord['uwr_createtime']         = current_datetime();

        $UserWithdrawRecordModel = Loader::model('UserWithdrawRecord');
        $UserWithdrawRecordModel->save($withdrawRecord);
        $withdrawRecordId = $UserWithdrawRecordModel->uwr_id;
        if(empty($withdrawRecordId)){
            Db::rollback();
            return false;
        }

        $cutAmountBalance  = bcsub($beforeUserBalance, $amount);
        $userAccountRecord = [];
        $userAccountRecord['user_id']                 = $userId;
        $userAccountRecord['uar_source_id']           = $withdrawRecordId;
        $userAccountRecord['uar_source_type']         = Config::get('status.user_account_record_source_type')['withdraw'];
        $userAccountRecord['uar_transaction_type']    = Config::get('status.account_record_transaction_type')['withdraw'];
        $userAccountRecord['uar_action_type']         = Config::get('status.account_record_action_type')['fetch'];
        $userAccountRecord['uar_amount']              = $amount;
        $userAccountRecord['uar_before_balance']      = $beforeUserBalance;
        $userAccountRecord['uar_after_balance']       = $cutAmountBalance;
        $userAccountRecord['uar_remark']              = '在线出款';
        $userAccountRecord['uar_status']              = Config::get('status.account_record_status')['no'];
        $userAccountRecordModel = Loader::model('UserAccountRecord');
        $accountResult = $userAccountRecordModel->insert($userAccountRecord);
        if(empty($accountResult)){
            Db::rollback();
            return false;
        }

        if($withdrawFee > 0){
            $cut_fee_balance = bcsub($cutAmountBalance, $withdrawFee);
            $userAccountRecord = [];
            $userAccountRecord['user_id']                 = $userId;
            $userAccountRecord['uar_source_id']           = $withdrawRecordId;
            $userAccountRecord['uar_source_type']         = Config::get('status.user_account_record_source_type')['withdraw'];
            $userAccountRecord['uar_transaction_type']    = Config::get('status.account_record_transaction_type')['withdraw_deduct'];
            $userAccountRecord['uar_action_type']         = Config::get('status.account_record_action_type')['fetch'];
            $userAccountRecord['uar_amount']              = $withdrawFee;
            $userAccountRecord['uar_before_balance']      = $cutAmountBalance;
            $userAccountRecord['uar_after_balance']       = $cut_fee_balance;
            $userAccountRecord['uar_remark']              = '在线出款手续费扣除';
            $userAccountRecord['uar_status']              = Config::get('status.account_record_status')['no'];
            $fee_result = $userAccountRecordModel->insert($userAccountRecord);

            if(empty($fee_result)){
                Db::rollback();
                return false;
            }
        }
        Db::commit();
        return $withdrawRecordId;
    }

    /**
     * 特殊代理提现
     * 
     */
    public function specialAgentWithdraw($amount){
        $userId = USER_ID;
        if (!$userId) {
            $this->errorcode = EC_USER_JWT_EXP_ERROR;
            return;
        }
        //验证余额
        $userBalance = Loader::model('UserExtend','logic')->getBalance($userId);
        if($userBalance < $amount){
            return show_response(EC_ORDER_BALANCE_NOT_ENOUGH, '余额不足！');
        }
        $withdrawId = $this->_saveSpecialAgentWithdrawRecord($userId, $amount);
        if ($withdrawId == false) {
            return show_response(EC_ORDER_BALANCE_NOT_ENOUGH, '余额不足！');
        }
    }


    private function _saveSpecialAgentWithdrawRecord($userId, $amount){

        Db::startTrans();
        $userBeforeBalance = Loader::model('UserExtend', 'logic')->getBalance($userId);

        $result = Loader::model('common/UserExtend', 'logic')->addWithdrawAmount($userId, $amount);
        if(empty($result)){
            Db::rollback();
            return false;
        }

        $userAfterBalance = Loader::model('UserExtend', 'logic')->getBalance($userId);
        if($userAfterBalance < 0){
            Db::rollback();
            return false;
        }

        $withdrawRecord = [];
        $withdrawRecord['user_id']                = $userId;
        $withdrawRecord['uwr_no']                 = generate_order_number();
        $withdrawRecord['uwr_type']               = Config::get('status.user_withdraw_type')['online'];
        $withdrawRecord['uwr_apply_amount']       = $amount;
        $withdrawRecord['uwr_handling_charge']    = 0;
        $withdrawRecord['uwr_account_balance']    = $userAfterBalance;
        $withdrawRecord['uwr_status']             = Config::get('status.withdraw_status')['confirm'];
        $withdrawRecord['uwr_createtime']         = current_datetime();

        $UserWithdrawRecordModel = Loader::model('UserWithdrawRecord');
        $withdrawRecordId = $UserWithdrawRecordModel->save($withdrawRecord);
        if(empty($withdrawRecordId)){
            Db::rollback();
            return false;
        }

        $cutAmountBalance = bcsub($userBeforeBalance, $amount);
        $userAccountRecord = [];
        $userAccountRecord['user_id']                 = $userId;
        $userAccountRecord['uar_source_id']           = $withdrawRecordId;
        $userAccountRecord['uar_source_type']         = Config::get('status.user_account_record_source_type')['withdraw'];
        $userAccountRecord['uar_transaction_type']    = Config::get('status.account_record_transaction_type')['withdraw'];
        $userAccountRecord['uar_action_type']         = Config::get('status.account_record_action_type')['fetch'];
        $userAccountRecord['uar_amount']              = $amount;
        $userAccountRecord['uar_before_balance']      = $userBeforeBalance;
        $userAccountRecord['uar_after_balance']       = $cutAmountBalance;
        $userAccountRecord['uar_remark']              = '在线出款';
        $userAccountRecord['uar_status']              = Config::get('status.account_record_status')['no'];
        $userAccountRecordModel = Loader::model('UserAccountRecord');
        $accountResult = $userAccountRecordModel->save($userAccountRecord);
        if(empty($accountResult)){
            Db::rollback();
            return false;
        }
   
        Db::commit();
        return $withdrawRecordId;
    }


}