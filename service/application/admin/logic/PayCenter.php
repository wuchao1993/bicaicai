<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;
use think\Log;

class PayCenter extends Model {

    public $errorcode = EC_AD_SUCCESS;

	public function getInfo(){
        return Loader::model('PayCenterMerchantInfo')->find();
    }

    public function setInfo($params){
        $data['merchant_id'] = $params['merchantId'];
        $data['sign_key'] = $params['signKey'];
        $data['notify_url'] = $params['notifyUrl'];
        $data['callback_url'] = $params['callbackUrl'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['edit_time'] = date('Y-m-d H:i:s');
        $result = Loader::model('PayCenterMerchantInfo')->insert($data);
        if($result) {
            return true;
        }else{
            return false;            
        }    
    }

    public function editInfo($params){
        $data['id'] = $params['id'];
        $data['merchant_id'] = $params['merchantId'];
        $data['sign_key'] = $params['signKey'];
        $data['notify_url'] = $params['notifyUrl'];
        $data['callback_url'] = $params['callbackUrl'];
        $data['edit_time'] = date('Y-m-d H:i:s');
        $result = Loader::model('PayCenterMerchantInfo')->update($data);
        if($result) {
            return true;
        }else{
            return false;            
        }    
    }

    public function getChannelList($params){
        $result = Loader::model('PayCenterChannel')->page($params['page'], $params['pageSize'])->select();
        $count = Loader::model('PayCenterChannel')->count();
        return [
            'list' => $result,
            'count' => $count
        ];
    }

    public function getChannelMerchantList($params){
        $pageSize = $params['num'] ? $params['num'] : 10;
        $page = $params['page'] ? $params['page'] : 1;
        $condition = [];
        $condition['m.status'] = array('LT',2);
        if(isset($params['status']) && $params['status'] != ''){
            $condition['m.status'] = $params['status'];
        }
        if(!empty($params['payChannelId'])){
            $condition['m.pay_channel_id'] = $params['payChannelId'];
        }
        if(!empty($params['userLevelId'])) {
            $payPlatformIds = Loader::model('PayCenterAccountUserLevelRelation', 'logic')->getPayCenterChannelIds($params['userLevelId']);
            $condition['m.channel_merchant_id'] = ['in', $payPlatformIds];
        }
        $list = Loader::model('payCenterChannelMerchant')
                ->alias('m')
                ->where($condition)
                ->limit($pageSize)
                ->page($page)
                ->order('m.status desc,m.channel_merchant_id desc')
                ->select();
        $list = collection($list)->toArray();
        $payCenterPayTypeMap = $this->getPayCenterPayTypeMap();
        $rechargeTypeIdCodeMap = Loader::model('common/RechargeType')->getRechargeTypeIdCodeMap();

        $channelNames =  Loader::model('PayCenterChannel')->column('name', 'pay_channel_id');

        foreach ($list as $key => $value) {
            $code = $payCenterPayTypeMap[$value['pay_type_id']];
            $list[$key]['category_name'] = $rechargeTypeIdCodeMap[$code]['recharge_type_name'];
            $list[$key]['pay_type_id'] = $rechargeTypeIdCodeMap[$code]['recharge_type_id'];
            if(isset($channelNames[$value['pay_channel_id']])){
                $list[$key]['name'] = $channelNames[$value['pay_channel_id']];
            }            
        }

        $count = Loader::model('payCenterChannelMerchant')
                ->alias('m')
                ->where($condition)
                ->count();
        if(!empty($list)) {
            foreach ($list as &$val) {
                $condition = [];
                $condition['p.channel_merchant_id'] = $val['channel_merchant_id'];
                $levelList = Loader::model('PayCenterAccountUserLevelRelation')
                            ->alias('p')
                            ->join('UserLevel ul','ul.ul_id=p.user_level_id', 'LEFT')
                            ->field('ul.ul_id as id,ul.ul_name as name')
                            ->where($condition)
                            ->select();
                $val['levelList'] = $levelList;
            }
        }

        return [
            'list' => $list,
            'count' => $count
        ];
    }
    
    public function createChannelMerchant($params, $userLevelId){
        $condition['recharge_type_id'] = $params['payTypeId'];
        $localPayTypeInfo = Loader::model('RechargeType')->where($condition)->find();
        $payTypeCode = $localPayTypeInfo['recharge_type_code'];
        $payCenterPayTypeMap = array_flip($this->getPayCenterPayTypeMap());
        //获取充值中心支付类型id
        $params['payTypeId'] = $payCenterPayTypeMap[$payTypeCode];
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.create_pay_channel_merchant');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        if($result['code'] === 108002){
            $this->errorcode = EC_AD_PAYTYPE_DISABLE;
            return false;            
        }
        if($result['code'] === 200){
            $data['channel_merchant_id'] = $result['data']['id'];
            $data['pay_channel_id'] = $params['payChannelId'];
            $data['pay_type_id'] = $params['payTypeId'];
            $data['redirect_domain'] = $params['redirectDomain'];
            $data['account'] = $params['account'] ? $params['account'] : '';
            $data['desc'] = $params['desc'] ? $params['desc'] : '';
            $data['md5_key'] = $params['md5Key'] ? $params['md5Key'] :'';
            $data['terminal_id'] = $params['terminalId'] ? $params['terminalId'] : 0;
            $data['recharge_count'] = $params['limitAmount'] ? $params['limitAmount'] : 0;
            $data['rsa_pri_key'] = $params['rsaPriKey'] ? $params['rsaPriKey'] : '';
            $data['rsa_public_key'] = $params['rsaPublicKey'] ? $params['rsaPublicKey'] : '';
            $data['create_time'] = time();
            $channelMerchant = Loader::model('PayCenterChannelMerchant');
            $channelMerchant->insert($data);
            if(!empty($userLevelId)) {
                foreach($userLevelId as $val) {
                    $userLevelData['channel_merchant_id'] = $result['data']['id'];
                    $userLevelData['user_level_id'] = $val;
                    Loader::model('PayCenterAccountUserLevelRelation')->insert($userLevelData,true);
                }
            }
        }else{
            $this->errorcode = EC_AD_CREATE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
    }

    public function getPayCenterPayTypeInfo($payTypeCode){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_pay_type_by_short_name');
        $signKey = $merchantInfo['sign_key'];
        $params['shortName'] = $payTypeCode;
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result['data']['id'];
    }

    public function updateChannelMerchant($params, $userLevelId){

        $condition['recharge_type_id'] = $params['payTypeId'];
        $localPayTypeInfo = Loader::model('RechargeType')->where($condition)->find();
        $payTypeCode = $localPayTypeInfo['recharge_type_code'];
        $payCenterPayTypeMap = array_flip($this->getPayCenterPayTypeMap());
        $payTypeId = $payCenterPayTypeMap[$payTypeCode];
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.update_pay_channel_merchant');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $params['payTypeId'] = $payTypeId;
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        $update['pay_channel_id'] = $params['payChannelId'];
        $update['pay_type_id'] = $payTypeId;
        $update['redirect_domain'] = $params['redirectDomain'];
        $update['account'] = $params['account'] ? $params['account'] : '';
        $update['desc'] = $params['desc'] ? $params['desc'] : '';;
        $update['md5_key'] = $params['md5Key'] ? $params['md5Key'] : '';
        $update['terminal_id'] = $params['terminalId'] ? $params['terminalId'] : 0;
        $update['limit_amount'] = $params['limitAmount'] ? $params['limitAmount'] : 0;
        $update['rsa_pri_key'] = $params['rsaPriKey'] ? $params['rsaPriKey'] : '';
        $update['rsa_public_key'] = $params['rsaPublicKey'] ? $params['rsaPublicKey'] : '';
        $update['channel_merchant_id'] = $params['channelMerchantId'];
        $channelMerchant = Loader::model('PayCenterChannelMerchant');
        $channelMerchant->update($update);
        $channelMerchantId = $params['channelMerchantId'];
        Loader::model('PayCenterAccountUserLevelRelation')->where(['channel_merchant_id' => $channelMerchantId])->delete();
        if(!empty($userLevelId)) {
            foreach($userLevelId as $val) {
                $data = [];
                $data['channel_merchant_id'] = $channelMerchantId;
                $data['user_level_id']   = $val;
                Loader::model('PayCenterAccountUserLevelRelation')->insert($data,true);
            }
        }
        if($result['code'] === 200) {
            return $result; 
        }else{
            $this->errorcode = EC_AD_UPDATE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
    }

    public function enableChannelMerchant($params){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.enable_pay_channel_merchant');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        if($result['code'] !== 200) {
            $this->errorcode = EC_AD_CHANGE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
        $channelMerchant = Loader::model('PayCenterChannelMerchant');
        $data['channel_merchant_id'] = $params['channelMerchantId'];
        $data['status'] = 1;
        $ret = $channelMerchant->update($data);
        if($ret){
            return $result;
        }else{
            $this->errorcode = EC_AD_CHANGE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
    }

    public function disableChannelMerchant($params){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.disable_pay_channel_merchant');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        if($result['code'] !== 200) {
            $this->errorcode = EC_AD_CHANGE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
        $channelMerchant = Loader::model('PayCenterChannelMerchant');
        $data['channel_merchant_id'] = $params['channelMerchantId'];
        $data['status'] = 0;
        $ret = $channelMerchant->update($data);
        if($ret){
            return $result;
        }else{
            $this->errorcode = EC_AD_CHANGE_CHANNEL_MERCHANT_ERROR;
            return false;
        }  	
    }

    public function deleteChannelMerchant($params){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.delete_pay_channel_merchant');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        if($result['code'] !== 200) {
            $this->errorcode = EC_AD_CHANGE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
        $channelMerchant = Loader::model('PayCenterChannelMerchant');
        $data['channel_merchant_id'] = $params['channelMerchantId'];
        $data['status'] = 2;
        $ret = $channelMerchant->update($data);
        if($ret){
            return $ret; 
        }else{
            $this->errorcode = EC_AD_CHANGE_CHANNEL_MERCHANT_ERROR;
            return false;
        }
    }

    public function getPayTypeList(){
        $payType = Loader::model('RechargeType');
        $list = $payType->select();
        return $list;
    }


    public function activeQuery($params){
        $apiUrl = Config::get('pay.pay_center_order_query_url');
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($apiUrl, $params), true);
        print_r($result);exit;
    }

    public function getPayCenterPayTypeMap(){
        $cacheName = Config::get('cache_option.prefix')['pay_center'] . 'payTypeList';
        $data = Cache::get($cacheName);
        if($data){
            return $data;
        }else{
            $apiUrl = Config::get('pay.pay_type_list_url');
            $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
            $signKey = $merchantInfo['sign_key'];
            $params['merchantId'] = $merchantInfo['merchant_id'];
            $params['nonce'] = random_string(32);
            $params['pageSize'] = 100;
            $sign = build_request_sign($params, $signKey);
            $params['sign'] = $sign;
            Log::write($apiUrl."请求的参数:". print_r($params, true));
            $result = json_decode(Curlrequest::post($apiUrl, $params), true);
            Log::write($apiUrl."返回的参数:". print_r($result, true));
            $list = array();
            foreach ($result['data']['list'] as &$value) {
                $list[$value['payTypeId']] = $value['shortName'];
            }
            Cache::set($cacheName, $list, 2*60);
            return $list;
        }    
    }

    public function callPayCenterApi($apiUrl,$requestData){
        $signKey = Config::get('pay.app_config')['sign_key'];
        $requestData['appId'] = Config::get('pay.app_config')['app_id'];
        $requestData['nonce'] = random_string(32);
        $sign = build_request_sign($requestData, $signKey);
        $requestData['sign'] = $sign;
        $result = json_decode(Curlrequest::post($apiUrl, $requestData), true);
        if($result['code'] == 200) {
            $data['code'] = 200;
            $data['message'] = $result['message'];
            $data['data'] = $result['data'];
            return $data;
        }else{
            $data['code'] = $result['code'];
            $data['message'] = $result['message'];
            return $data;
        }
    }

}