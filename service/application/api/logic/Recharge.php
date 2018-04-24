<?php

namespace app\api\logic;

use app\collect\logic\InPlayNow;
use think\Cache;
use think\Model;
use think\Config;
use think\Loader;
use Filipac\Ip;
use think\Log;
use curl\Curlrequest;

class Recharge extends Model
{

    public $errorcode = EC_SUCCESS;


    /**
     * 生成好友支付二维码
     * @param $categoryId
     * @return array
     */
    public function getFriendQrCode($categoryId)
    {
        $userId = USER_ID;
        $userInfo = Loader::model('User', 'logic')->getInfo($userId);
        $userLevelId = $userInfo['ul_id'];
        $pay_account_ids = Loader::model('PayAccountUserLevelRelation', 'logic')->getPayAccountIds($userLevelId);
        if ($pay_account_ids) {
            $pay_account_info = Loader::model('PayAccount', 'logic')->getQrCodeInfo($pay_account_ids, $categoryId);
            $pay_url = $pay_account_info['pa_code_url'];
            if ($pay_url) {
                $response = [
                    'qrCodeUrl' => build_website($pay_url),
                    'account' => $pay_account_info['pa_collection_account'],
                    'payAccountId' => $pay_account_info['pa_id'],
                ];

                return show_response(EC_SUCCESS, '请求成功', $response);
            } else {
                return show_response(EC_RECHARGE_TYPE_NOT_SUPPORT, '暂不支持此支付方式！');
            }
        } else {
            return show_response(EC_RECHARGE_TYPE_NOT_SUPPORT, '暂不支持此支付方式！');
        }
    }

    public function getPayCenterPayTypeInfo($payTypeCode){
        $merchanInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_pay_type_by_short_name');
        $signKey = $merchanInfo['sign_key'];
        $params['shortName'] = $payTypeCode;
        $params['merchantId'] = $merchanInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result;   
    }

    public function pay($amount, $categoryId, $bankId = 0)
    {
        $userId = USER_ID;
        $userInfo = Loader::model('User', 'logic')->getInfo($userId);
        $userLevelId = $userInfo['ul_id'];
        $condition['recharge_type_id'] = $categoryId;
        $localPayTypeInfo = Loader::model('RechargeType')->where($condition)->find(); 
        $payTypeCode = $localPayTypeInfo['recharge_type_code'];
        if(empty($payTypeCode)){
            Log::write(__CLASS__.__FUNCTION__.'支付类型code未设置');
            return show_response(EC_RECHARGE_CHANNEL_DISABLE, Config::get('errorcode')[EC_RECHARGE_CHANNEL_DISABLE]);
        }

        //获取充值中心支付类型id
        $payTypeInfo = $this->getPayCenterPayTypeInfo($payTypeCode);
        $payPlatformInfo = $this->getPayChannelInfo($userLevelId, $payTypeInfo['data']['id'], $bankId);
        if (empty($payPlatformInfo)) {
            Log::write('step-1:' . Config::get('errorcode')[EC_RECHARGE_CHANNEL_DISABLE]);
            return show_response(EC_RECHARGE_CHANNEL_DISABLE, Config::get('errorcode')[EC_RECHARGE_CHANNEL_DISABLE]);
        }
        $payConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($userLevelId);
        if (empty($payConfig)) {
            Log::write('step-2:' . Config::get('errorcode')[EC_LACK_CONFIG]);
            return show_response(EC_LACK_CONFIG, Config::get('errorcode')[EC_LACK_CONFIG]);
        }
        $discountPercentage = $payConfig['pc_online_discount_percentage'];     //线上入款优惠
        $discountStartAmount = $payConfig['pc_online_discount_start_amount'];  //线上存款优惠标准（优惠起始额度）
        $rechargeTrafficMutiple = $payConfig['pc_recharge_traffic_mutiple'];
        $discountTrafficMutiple = $payConfig['pc_discount_traffic_mutiple'];
        $rechargeMaxAmount = $payConfig['pc_online_recharge_max_amount'];
        $rechargeMinAmount = $payConfig['pc_online_recharge_min_amount'];
        $maxDiscount = $payConfig['pc_online_discount_max_amount'];

        if ($amount < $rechargeMinAmount) {
            return show_response(EC_RECHARGE_AMOUNT_LIMIT, '在线充值最低额度为' . $rechargeMinAmount . '元');
        }
        if ($amount > $rechargeMaxAmount) {
            return show_response(EC_RECHARGE_AMOUNT_LIMIT, '在线充值最高额度为' . $rechargeMaxAmount . '元');
        }

        if ($discountPercentage > 0 && bccomp($amount, $discountStartAmount) >= 0) {
            $discount = bcmul($amount, bcdiv($discountPercentage, 100));
        } else {
            $discount = 0;
        }

        if (bccomp($discount, $maxDiscount) > 0) {
            $discount = $maxDiscount;
        }

        $rechargeTraffic = bcmul($amount, $rechargeTrafficMutiple);
        $discountTraffic = bcmul($discount, $discountTrafficMutiple);
        $trafficAmount = bcadd($rechargeTraffic, $discountTraffic);

        $payPlatformId = $payPlatformInfo['channel_merchant_id'];
        $orderCreateTime = current_datetime();
        $clientIp = Ip::get();
        $orderNo = generate_order_number();

        $userRechargeRecordId = Loader::model('UserRechargeRecord', 'logic')->addOnlineRecord($orderNo, $userId, $payPlatformId, $amount, $discount, $trafficAmount, $orderCreateTime, $clientIp);
        if ($userRechargeRecordId) {
            $bankInfo = Loader::model('Bank', 'logic')->getInfoById($bankId);
            $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
            $params = [
                'merchantId'  => $merchantInfo['merchant_id'],
                'channelMerchantId' => $payPlatformId,
                'outTradeNo'  => $orderNo,
                'totalFee'    => $amount,
                'payType'     => $payTypeInfo['data']['shortName'],
                'bankCode'    => $bankInfo['bank_code'],
                'tradeTime'   => $orderCreateTime,
                'notifyUrl'   => $merchantInfo['notify_url'],
                'callbackUrl' => $merchantInfo['callback_url'],
                'attach'      => $orderNo,
                'clientIp'    => $clientIp,
                'nonce'       => random_string(32),
            ];
            $result = $this->payCenterCreateOrder($params, $merchantInfo['sign_key']);
            Log::write('pay_api_result:' . print_r($result, true));
            if($result == false){
                return show_response(EC_PAY_CENTER_ERROR, '预支付处理失败！');
            } else if ($result['code'] === 200) {
                $updateInfo = [];
                $updateInfo['urr_id'] = $userRechargeRecordId;
                $updateInfo['urr_trade_no'] = $result['data']['payCenterOrderNo'];
                Loader::model('UserRechargeRecord')->update($updateInfo);
                $response['codeImgUrl'] = $result['data']['codeImageUrl'];
                $response['rechargeUrl'] = $result['data']['rechargeUrl'];
                $response['amount'] = $result['data']['totalFee'];
                $response['orderNo'] = $orderNo;
                Log::write('digital-return:' . print_r($result, true));

                return show_response(EC_SUCCESS, '预支付处理成功！', output_format($response));
            } else {
                $errorMessage = Config::get('pay.pay_center_error')[$result['code']];
                return show_response(EC_PAY_CENTER_ERROR, $errorMessage ? $errorMessage : $result['message']);
            }
        } else {
            return show_response(EC_DATABASE_ERROR, '预支付处理失败');
        }
    }

    
    public function payCenterCreateOrder($params, $signKey){
        $url = Config::get('pay.create_order_url');
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $data = json_decode(Curlrequest::post($url, $params), true);
        
        return $data;
    }


    public function specialAgentPay($amount)
    {

        $this->db()->startTrans();
        $userId = USER_ID;
        $discount = 0;
        $trafficAmount = 0;
        $payPlatformId = 0;
        $orderCreateTime = current_datetime();
        $clientIp = Ip::get();
        $orderNo = generate_order_number();
        $orderId = Loader::model('UserRechargeRecord', 'logic')->addOnlineRecord($orderNo, $userId, $payPlatformId, $amount, $discount, $trafficAmount, $orderCreateTime, $clientIp);
        if ($orderId) {
            $result = $this->addRechargeAmount($userId, $amount, $discount);
            if (empty($result)) {
                $this->db()->rollback();
                return show_response(EC_DATABASE_ERROR, '支付失败！');
            }
            if (!$this->addUserAccountRecord($userId, $orderId, $amount)) {
                $this->db()->rollback();
                return show_response(EC_DATABASE_ERROR, '支付失败！');
            }
        } else {
            $this->db()->rollback();
            return show_response(EC_DATABASE_ERROR, '支付失败！');
        }
        $status = Loader::model('UserRechargeRecord', 'logic')->updateSpecialRechargeStatus($orderId);
        if(!$status){
            $this->db()->rollback();
            return show_response(EC_DATABASE_ERROR, '支付失败！');            
        }
        $this->db()->commit();
        return show_response(EC_SUCCESS, '支付成功！', output_format($result));
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

    /**
     * 添加账户流水
     * @param $userId
     * @param $orderId
     * @param $amount
     * @return array()
     */
    public function addUserAccountRecord($userId, $orderId, $amount)
    {
        $userBeforeBalance = Loader::model('UserExtend', 'logic')->getBalance($userId);
        $userAfterBalance = bcadd($userBeforeBalance, $amount, 3);
        $data = array();
        $data['user_id'] = $userId;
        $data['uar_source_id'] = $orderId;
        $data['uar_source_type'] = SOURCE_TYPE_RECHARGE;
        $data['uar_transaction_type'] = Config::get('status.user_recharge_type')['online'];
        $data['uar_action_type'] = ACCOUNT_TRANSFER_IN;
        $data['uar_amount'] = $amount;
        $data['uar_before_balance'] = $userBeforeBalance;
        $data['uar_after_balance'] = $userAfterBalance;
        $data['uar_remark'] = '特殊代理在线充值';
        $userAccountRecord = Loader::model('UserAccountRecord');
        $result = $userAccountRecord->save($data);
        if ($result) {
            return ture;
        } else {
            return false;
        }
    }


    /**
     * 获取充值类型列表
     * @return bool | array
     */
    public function getRechargeTypeList()
    {
        $where = [
            'recharge_type_status' => Config::get('status.recharge_type_status')['normal'],
        ];

        $list = Loader::model('RechargeType')->where($where)->select();

        if (!$list) {
            return false;
        }

        $rechargeTypeGroups = Loader::model('RechargeTypeGroup')->select();

        $response = [];

        foreach ($rechargeTypeGroups as $groupKey => $group) {
            $groupId = $group['rtg_id'];
            $groupName = $group['rtg_name'];
            $rechargeTypeList = [];
            foreach ($list as $rechargeTypeInfo) {
                $actionType = $rechargeTypeInfo['recharge_type_action_type'];
                if ($actionType == 0) {
                    continue;
                }

                if ($rechargeTypeInfo['rtg_id'] == $groupId) {

                    $rechargeTypeList[] = [
                        'id' => $rechargeTypeInfo['recharge_type_id'],
                         'name' => $rechargeTypeInfo['recharge_type_name'],
                        'image' => $rechargeTypeInfo['recharge_type_image'],
                        'desc' => $rechargeTypeInfo['recharge_type_sort'],
                        'actionType' => $actionType,
                        'shortName' => $rechargeTypeInfo['recharge_type_short_name'],
                        'scheme' => $rechargeTypeInfo['recharge_type_scheme'],
                        'introduction' => $rechargeTypeInfo['recharge_type_introduction']
                    ];
                }
            }
            $response[] = [
                'groupId' => $groupId,
                'groupName' => $groupName,
                'rechargeTypeList' => $rechargeTypeList,
            ];
        }

        return $response;
    }

    public function getPayChannelInfo($userLevelId, $rechargeTypeId, $bankId){
        $payPlatformList = Loader::model('common/PayCenterChannelMerchant')->getChannelListByUserLevelId($userLevelId, $rechargeTypeId);
        if($bankId){
            $channelMerchantIds = Loader::model('common/PayCenterChannelBankRelation')->getChannelMerchantId($bankId);
            $payCenterChannelMerchantIds = extract_array($payPlatformList, 'pay_channel_id');
            $enableMerchantIds = array_intersect($channelMerchantIds, $payCenterChannelMerchantIds);
        }else{
            $enableMerchantIds = extract_array($payPlatformList, 'pay_channel_id');
        }
        $enableList = [];
        foreach ($payPlatformList as $payPlatform) {
            if(! in_array($payPlatform['pay_channel_id'], $enableMerchantIds)){
                continue;
            }
            if ($payPlatform['limit_amount'] == 0 || $payPlatform['limit_amount'] > $payPlatform ['recharge_count']) {
                $enableList[] = $payPlatform;
            }
        }

        if (count($enableList) > 0) {
            return $enableList[array_rand($enableList)];
        }
    }


    public function getPayPlatformInfo($userLevelId, $rechargeTypeId, $bankId)
    {
        Log::write('getPayPlatformInfo-params,userLevelId:' . $userLevelId . ',rechargeTypeId:' . $rechargeTypeId . ',bankId:' . $bankId);
        $payTypeIds = Loader::model('PayBankRelation', 'logic')->getPayTypeIds($bankId);
        Log::write('payTypeIds:' . print_r($payTypeIds, true));
        $payPlatformList = Loader::model('PayPlatform', 'logic')->getListByUserLevelId($userLevelId, $rechargeTypeId);
        Log::write('payPlatformList:' . print_r($payPlatformList, true));
        $enableList = [];
        foreach ($payPlatformList as $payPlatform) {
            if ($payPlatform['pp_limit_amount'] == 0 || $payPlatform['pp_limit_amount'] > $payPlatform ['pp_recharge_amount']) {
                if (is_array($payTypeIds) && sizeof($payTypeIds) > 0) {
                    if (in_array($payPlatform['pay_type_id'], $payTypeIds)) {
                        $enableList[] = $payPlatform;
                    }
                } else {
                    $enableList[] = $payPlatform;
                }
            }
        }
        Log::write('enableList:' . print_r($enableList, true));
        if (count($enableList) > 0) {
            return $enableList[array_rand($enableList)];
        }
    }


    public function friendPay($params)
    {
        $userId = USER_ID;
        $rechargeAccountId = $params['rechargeAccountId'];

        if (is_test_user($userId)) {
            return show_response(EC_TEST_USER_CAN_NOT_RECHARGE, Config::get('errorcode'))[EC_TEST_USER_CAN_NOT_RECHARGE];
        }

        $rechargeBankId = Loader::model('PayAccount')->getBankId($rechargeAccountId);

        if (empty($rechargeBankId)) {
            return show_response(EC_RECHARGE_TYPE_NOT_SUPPORT, Config::get('errorcode'))[EC_RECHARGE_TYPE_NOT_SUPPORT];
        }

        $rechargeAmount = $params['amount'];
        $rechargeDate = $params['rechargeDate'];

        $userInfo = Loader::model('User', 'logic')->getInfo($userId);
        $userLevelId = $userInfo['ul_id'];
        $payConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($userLevelId);

        $discountPercentage = $payConfig['pc_company_discount_percentage'];
        $discountStartAmount = $payConfig['pc_company_discount_start_amount'];
        $rechargeTrafficMutiple = $payConfig['pc_recharge_traffic_mutiple'];
        $discountTrafficMutiple = $payConfig['pc_discount_traffic_mutiple'];
        $rechargeMaxAmount = $payConfig['pc_company_recharge_max_amount'];
        $rechargeMinAmount = $payConfig['pc_company_recharge_min_amount'];
        $maxDiscount = $payConfig['pc_company_discount_max_amount'];

        bcscale(3);

        if (bccomp($rechargeAmount, $rechargeMinAmount) < 0) {
            return show_response(EC_RECHARGE_AMOUNT_LIMIT, '最小充值额度' . $rechargeMinAmount . '元.');
        }

        if (bccomp($rechargeAmount, $rechargeMaxAmount) > 0) {
            return show_response(EC_RECHARGE_AMOUNT_LIMIT, '最大充值额度' . $rechargeMaxAmount . '元.');
        }

        if ($discountPercentage > 0 && $rechargeAmount > $discountStartAmount) {
            $discount = bcmul($rechargeAmount, $discountPercentage / 100);
        } else {
            $discount = 0;
        }

        if (bccomp($discount, $maxDiscount) > 0) {
            $discount = $maxDiscount;
        }

        $rechargeTraffic = bcmul($rechargeAmount, $rechargeTrafficMutiple);
        $discountTraffic = bcmul($discount, $discountTrafficMutiple);

        $rechargeRecord = [
            'urr_no' => generate_order_number(),
            'user_id' => $userId,
            'urr_recharge_account_id' => $rechargeAccountId,
            'urr_type' => Config::get('status.user_recharge_type')['online'],
            'urr_amount' => $rechargeAmount,
            'urr_recharge_discount' => $discount,
            'urr_total_amount' => bcadd($rechargeTraffic, $discountTraffic),
            'urr_traffic_amount' => bcadd($rechargeTraffic, $discountTraffic),
            'urr_required_bet_amount' => 0,
            'urr_recharge_bank_id' => $rechargeBankId,
            'urr_recharge_time' => $rechargeDate,
            'urr_client_ip' => IP::get(),
            'urr_status' => Config::get('status.recharge_status')['wait'],
            'urr_remark' => $params['remark'],
        ];

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');
        $result = $userRechargeRecordModel->save($rechargeRecord);
        if ($result) {
            $rechargeId = $userRechargeRecordModel->urr_id;
            if ($rechargeId) {
                return show_response(EC_SUCCESS, '提交成功！');
            } else {
                return show_response(EC_DATABASE_ERROR, '提交失败！');
            }
        }
    }


    public function addCompanyRecharge($params)
    {
        $userId = USER_ID;
        $rechargeUserName = $params['userName'];
        $rechargeAmount = $params['amount'];
        $rechargeAccountId = $params['rechargeAccountId'];
        $rechargeType = $params['rechargeType'];
        $rechargeDate = $params['rechargeDate'];
        $rechargeBankId = $params['rechargeBankId'];

        $userInfo = Loader::model('User', 'logic')->getInfoByUid($userId);
        $userLevelId = $userInfo['ul_id'];
        $payConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($userLevelId);

        $discountPercentage = $payConfig['pc_company_discount_percentage'];
        $discountStartAmount = $payConfig['pc_company_discount_start_amount'];
        $rechargeTrafficMutiple = $payConfig['pc_recharge_traffic_mutiple'];
        $discountTrafficMutiple = $payConfig['pc_discount_traffic_mutiple'];
        $rechargeMaxAmount = $payConfig['pc_company_recharge_max_amount'];
        $rechargeMinAmount = $payConfig['pc_company_recharge_min_amount'];
        $maxDiscount = $payConfig['pc_company_discount_max_amount'];
        bcscale(3);

        if (bccomp($rechargeAmount, $rechargeMinAmount) < 0) {
            return show_response(EC_RECHARGE_AMOUNT_LIMIT, '最小充值额度' . $rechargeMinAmount . '元.');
        }

        if (bccomp($rechargeAmount, $rechargeMaxAmount) > 0) {
            return show_response(EC_RECHARGE_AMOUNT_LIMIT, '最大充值额度' . $rechargeMaxAmount . '元.');
        }

        if ($discountPercentage > 0 && $rechargeAmount > $discountStartAmount) {
            $discount = bcmul($rechargeAmount, $discountPercentage / 100);
        } else {
            $discount = 0;
        }

        if (bccomp($discount, $maxDiscount) > 0) {
            $discount = $maxDiscount;
        }

        $recharge_traffic = bcmul($rechargeAmount, $rechargeTrafficMutiple);
        $discount_traffic = bcmul($discount, $discountTrafficMutiple);

        $rechargeRecord = [];
        $rechargeRecord['urr_no'] = generate_order_number();
        $rechargeRecord['user_id'] = $userId;
        $rechargeRecord['urr_recharge_account_id'] = $rechargeAccountId;
        $rechargeRecord['urr_type'] = Config::get('status.user_recharge_type')['company'];
        $rechargeRecord['urr_transfer_type'] = $rechargeType;
        $rechargeRecord['urr_amount'] = $rechargeAmount;
        $rechargeRecord['urr_recharge_discount'] = $discount;
        $rechargeRecord['urr_total_amount'] = bcadd($rechargeAmount, $discount);
        $rechargeRecord['urr_traffic_amount'] = bcadd($recharge_traffic, $discount_traffic);
        $rechargeRecord['urr_required_bet_amount'] = 0;
        $rechargeRecord['urr_recharge_bank_id'] = $rechargeBankId;
        $rechargeRecord['urr_recharge_user_name'] = $rechargeUserName;
        $rechargeRecord['urr_recharge_time'] = $rechargeDate;
        $rechargeRecord['urr_client_ip'] = IP::get();
        $rechargeRecord['urr_status'] = Config::get('status.recharge_status')['wait'];
        $rechargeRecord['urr_remark'] = $params['remark'] ? $params['remark'] : '';
//        $rechargeRecord['urr_is_first'] = $this->_checkFirstRecharge($userId);

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');
        $result = $userRechargeRecordModel->save($rechargeRecord);
        if ($result) {
            $rechargeId = $userRechargeRecordModel->urr_id;
            if ($rechargeId) {
                return show_response(EC_SUCCESS, '提交成功！');
            } else {
                return show_response(EC_DATABASE_ERROR, '提交失败！');
            }
        }
    }



    public function searchOrderState($orderId)
    {
        $userId = USER_ID;
        $userInfo = Loader::model('User', 'logic')->getInfo($userId);
        $rechargeInfo = Loader::model('UserRechargeRecord')->getInfoByOrderId($orderId);
        if (empty($rechargeInfo) || $userInfo['user_id'] != $rechargeInfo['user_id']) {
            $this->errorcode = EC_RECHARGE_RECORD_NOT_EXIST;
            return false;
        }
        $rechargeState = $rechargeInfo['urr_status'];
        if ($rechargeState == Config::get('status.recharge_status')['untreated']) {
            if ($rechargeInfo['urr_type'] == Config::get('status.user_recharge_type')['online']) {
                $params = [
                    'act' => DIGITAL_BUDAN_ACTION,
                    'order_id' => $orderId,
                ];
                $result = call_to_digital($params);
                if ($result['status'] == 0) {
                    return true;
                } else {
                    $this->errorcode = EC_RECHARGE_UNTREATED;
                    return false;
                }
            } else {
                $this->errorcode = EC_RECHARGE_UNTREATED;
                return false;
            }
        } elseif ($rechargeState == Config::get('status.recharge_status')['fail']) {
            $this->errorcode = EC_RECHARGE_FAIL;

            return false;
        } elseif ($rechargeState == Config::get('status.recharge_status')['cancel']) {
            $this->errorcode = EC_RECHARGE_CANCEL;
            return false;
        } else {
            return true;
        }
    }


    public function getRechargeDetail($orderNo)
    {
        $userId = USER_ID;
        $userInfo = Loader::model('User', 'logic')->getInfo($userId);
        $rechargeInfo = Loader::model('UserRechargeRecord')->getInfoByOrderId($orderNo);
        if (empty($rechargeInfo) || $userInfo['user_id'] != $rechargeInfo['user_id']) {
            $this->errorcode = EC_RECHARGE_RECORD_NOT_EXIST;
            return false;
        }

        $response = [
            "rechargeId" => $rechargeInfo['urr_id'],
            "rechargeNo" => $rechargeInfo['urr_no'],
            "rechargeAmount" => $rechargeInfo['urr_amount'],
            "rechargeDiscount" => $rechargeInfo['urr_recharge_discount'],
            "rechargeType" => Config::get('status.user_recharge_type_name')[$rechargeInfo['urr_type']],
            "statusName" => Config::get('status.recharge_status_name')[$rechargeInfo['urr_status']],
            "status" => $rechargeInfo['urr_status'],
            "datetime" => $rechargeInfo['urr_createtime'],
        ];

        return $response;
    }

}