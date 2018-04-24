<?php
namespace app\pay\logic;

use think\Loader;
use think\Config;
use think\Log;
use Filipac\Ip;

class Recharge{

    public $errorcode = EC_SUCCESS;
    public $errorMessage = "";

    private $_payConfig = [];
    private $_amount = 0;
    private $_discountAmount = 0;
    private $_trafficAmount = 0;

    public function __construct()
    {

    }

    public function onlineRecharge($params){
        $bankCode = $params['bankCode'];
        $merchantId = $params['merchantId'];
        $this->_amount = $params['amount'];
        $terminal = $params['terminal'];

        $preProcessResult = $this->_preProcess();
        if($preProcessResult == false){
            return false;
        }

        $orderNo = generate_order_number();
        $userRechargeRecordId = $this->_addUserRechargeRecord($merchantId, $orderNo);
        if ($userRechargeRecordId) {
            $result = $this->_createOderFromPayCenter($merchantId, $orderNo, $bankCode, $terminal);
            if($result == false){
                $this->errorcode = EC_PAY_CENTER_ERROR;
                $this->errorMessage = '预支付处理失败';
                return false;
            }
            $this->_updateUserRechargeRecord($userRechargeRecordId, $result['data']['payCenterOrderNo']);
            $this->errorMessage = "预支付处理成功!";
            return $this->_buildResponse($result, $orderNo);
        } else {
            $this->errorcode = EC_DATABASE_ERROR;
            $this->errorMessage = '预支付处理失败';
            return false;
        }
    }


    private function _preProcess(){
        $userLevelId = $this->_getUserLevelId(USER_ID);
        $payConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($userLevelId);
        if (empty($payConfig)) {
            $this->errorcode = EC_LACK_CONFIG;
            return false;
        }
        $this->_payConfig = $payConfig;
        if($this->_checkPayConfig() == false){
            return false;
        }
        $this->_calculateRechargeInfo();

        return true;
    }

    private function _calculateRechargeInfo(){
        $this->_discountAmount = $this->_calculateDiscount();
        $rechargeTraffic = bcmul($this->_amount, $this->_payConfig['pc_recharge_traffic_mutiple']);
        $discountTraffic = bcmul($this->_discountAmount, $this->_payConfig['pc_discount_traffic_mutiple']);
        $this->_trafficAmount = bcadd($rechargeTraffic, $discountTraffic);
    }

    private function _addUserRechargeRecord($merchantId, $orderNo){
        $orderInfo =[
            'urr_no' => $orderNo,
            'user_id' => USER_ID,
            'urr_recharge_account_id' => $merchantId,
            'urr_type' => Config::get('status.user_recharge_type')['online'],
            'urr_amount' => $this->_amount,
            'urr_recharge_discount' => $this->_discountAmount,
            'urr_total_amount' => bcadd($this->_amount, $this->_discountAmount, 2),
            'urr_traffic_amount' => $this->_trafficAmount,
            'urr_required_bet_amount' => 0,
            'urr_client_ip' => Ip::get(),
            'urr_status' => Config::get('status.recharge_status')['wait'],
            'urr_createtime' => current_datetime(),
        ];
        $model = Loader::model('UserRechargeRecord');
        $result = $model->save($orderInfo);

        return $result ? $model->urr_id : false;
    }


    private function _createOderFromPayCenter($merchantId, $orderNo, $bankCode = '', $terminal='unknow'){
        $appConfig = Config::get('pay.app_config');
        $params = [
            'merchantId' => $merchantId,
            'outTradeNo'  => $orderNo,
            'totalFee'    => $this->_amount,
            'terminal'    => $terminal,
            'bankCode'    => $bankCode,
            'tradeTime'   => current_datetime(),
            'notifyUrl'   => $appConfig['notify_url'],
            'callbackUrl' => $appConfig['callback_url'],
            'attach'      => $orderNo,
            'clientIp'    => Ip::get(),
        ];
        $result = call_pay_center_api(Config::get('pay.api_pay_center_create_order_v2'), $params);
        Log::write('pay_api_result:' . print_r($result, true));
        if($result['code'] == EC_SUCCESS){
            return $result['data'];
        }

        return false;
    }


    private function _updateUserRechargeRecord($userRechargeRecordId, $payCenterOrderNo){
        $updateInfo = [];
        $updateInfo['urr_id'] = $userRechargeRecordId;
        $updateInfo['urr_trade_no'] = $payCenterOrderNo;
        $result = Loader::model('UserRechargeRecord')->update($updateInfo);
        if($result == false){
            Log::write("更新充值记录信息出错");
        }
    }


    private function _buildResponse($data, $orderNo){
        $response['codeImgUrl'] = $data['codeImageUrl'];
        $response['rechargeUrl'] = $data['rechargeUrl'];
        $response['amount'] = $data['totalFee'];
        $response['orderNo'] = $orderNo;

        return $response;
    }


    public function friendRecharge($params){
        $rechargeAccountId = $params['rechargeAccountId'];
        //TODO 测试试玩库账号请求风险
        $rechargeBankId = $this->_checkFriendRechargeAccount($rechargeAccountId);
        if($rechargeBankId == false){
            return false;
        }
        $this->_amount = $params['amount'];
        $rechargeDate = $params['rechargeDate'];
        $remark = $params['remark'];

        $preProcessResult = $this->_preProcess();
        if($preProcessResult == false){
            return false;
        }

        $rechargeId = $this->_addFriendRechargeRecord($rechargeAccountId, $rechargeDate, $rechargeBankId, $remark);
        if ($rechargeId == false) {
            $this->errorcode = EC_DATABASE_ERROR;
            return false;
        }
    }


    private function _checkFriendRechargeAccount($rechargeAccountId){
        return $rechargeAccountId;
    }


    private function _addFriendRechargeRecord($rechargeAccountId,$rechargeDate,$rechargeBankId, $remark){
        $rechargeRecord = [
            'urr_no' => generate_order_number(),
            'user_id' => USER_ID,
            'urr_recharge_account_id' => $rechargeAccountId,
            'urr_type' => Config::get('status.user_recharge_type')['online'],
            'urr_amount' => $this->_amount,
            'urr_recharge_discount' => $this->_discountAmount,
            'urr_total_amount' => bcadd($this->_amount, $this->_discountAmount),
            'urr_traffic_amount' => $this->_trafficAmount,
            'urr_required_bet_amount' => 0,
            'urr_recharge_bank_id' => $rechargeBankId,
            'urr_recharge_time' => $rechargeDate,
            'urr_client_ip' => IP::get(),
            'urr_status' => Config::get('status.recharge_status')['wait'],
            'urr_remark' => $remark,
        ];
        $model = Loader::model('UserRechargeRecord');
        $result = $model->save($rechargeRecord);

        return $result ? $model->urr_id : false;
    }


    private function _checkPayConfig(){
        $rechargeMaxAmount = $this->_payConfig['pc_company_recharge_max_amount'];
        $rechargeMinAmount = $this->_payConfig['pc_company_recharge_min_amount'];
        if (bccomp($this->_amount, $rechargeMinAmount) < 0) {
            $this->errorcode = EC_RECHARGE_AMOUNT_LIMIT;
            $this->errorMessage = '最小充值额度' . $rechargeMinAmount . '元.';
            return false;
        }
        if (bccomp($this->_amount, $rechargeMaxAmount) > 0) {
            $this->errorcode = EC_RECHARGE_AMOUNT_LIMIT;
            $this->errorMessage = '最大充值额度' . $rechargeMaxAmount . '元.';
            return false;
        }
        return true;
    }


    private function _calculateDiscount(){
        $discountPercentage = $this->_payConfig['pc_company_discount_percentage'];
        $discountStartAmount = $this->_payConfig['pc_company_discount_start_amount'];
        $maxDiscount = $this->_payConfig['pc_company_discount_max_amount'];
        if ($discountPercentage > 0 && $this->_amount > $discountStartAmount) {
            $discount = bcmul($this->_amount, $discountPercentage / 100);
        } else {
            $discount = 0;
        }
        if (bccomp($discount, $maxDiscount) > 0) {
            $discount = $maxDiscount;
        }

        return $discount;
    }


    public function companyRecharge($params){
        $this->_amount = $params['amount'];

        $preProcessResult = $this->_preProcess();
        if($preProcessResult == false){
            return false;
        }

        $rechargeId = $this->_addCompanyRechargeRecord($params);
        if ($rechargeId == false) {
            $this->errorcode = EC_DATABASE_ERROR;
            return false;
        }

        return true;
    }


    private function _addCompanyRechargeRecord($params) {
        $rechargeRecord = [];
        $rechargeRecord['urr_no'] = generate_order_number();
        $rechargeRecord['user_id'] = USER_ID;
        $rechargeRecord['urr_recharge_account_id'] = $params['rechargeAccountId'];
        $rechargeRecord['urr_type'] = Config::get('status.user_recharge_type')['company'];
        $rechargeRecord['urr_transfer_type'] = $params['rechargeType'];
        $rechargeRecord['urr_amount'] = $this->_amount;
        $rechargeRecord['urr_recharge_discount'] = $this->_discountAmount;
        $rechargeRecord['urr_total_amount'] = bcadd($this->_amount, $this->_discountAmount);
        $rechargeRecord['urr_traffic_amount'] = $this->_trafficAmount;
        $rechargeRecord['urr_required_bet_amount'] = 0;
        $rechargeRecord['urr_recharge_bank_id'] = $params['rechargeBankId'];
        $rechargeRecord['urr_recharge_user_name'] = $params['userName'];
        $rechargeRecord['urr_recharge_time'] = $params['rechargeDate'];
        $rechargeRecord['urr_client_ip'] = IP::get();
        $rechargeRecord['urr_status'] = Config::get('status.recharge_status')['wait'];
        $rechargeRecord['urr_remark'] = $params['remark'] ? $params['remark'] : '';

        $model = Loader::model('UserRechargeRecord');
        $result = $model->save($rechargeRecord);

        return $result ? $model->urr_id : false;
    }


    private function _getUserLevelId($userId){
        $userInfo = Loader::model('Common/User', 'logic')->getInfoByUid($userId);
        return $userInfo['ul_id'];
    }

}