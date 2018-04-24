<?php
namespace app\collect\logic;

use think\Loader;
use think\Model;
use think\Config;
use curl\Curlrequest;
use think\Log;

class PayCenter {

    public function getBankList(){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.pay_center_channel_merchant_bank');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['pageSize'] = 1000;
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result;     
    }

    public function updateBank(){
        $list = input_format($this->getBankList());
        $list = $list['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key]['pc_id'] = $value['pay_channel_id'];
            unset($list[$key]['pay_channel_id']);
        }
        $channelBank = Loader::model('PayCenterChannelBankRelation');
        $channelBank->where("1=1")->delete();
        $status = $channelBank->insertAll($list, true);
        if ($status) {
            Log::info('充值中心渠道商户银行列表最后更新时间' . date('Y-m-d H:i:s'));
        }else{
            Log::error('更新充值渠道商户中心银行列表失败' . date('Y-m-d H:i:s'));
        }
    }

    public function getPayCenterChannelList(){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_pay_channel_list_url');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['pageSize'] = 100;
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result;
    }

    public function updateChannelList(){
        $list = input_format($this->getPayCenterChannelList());
        $list = $list['data']['list'];
        $channel = Loader::model('PayCenterChannel');
        $channel->where("1=1")->delete();
        $status = $channel->insertAll($list, true);
        if ($status) {
            Log::info('充值中心渠道列表最后更新时间' . date('Y-m-d H:i:s'));
        }else{
            Log::error('更新充值中心渠道列表失败' . date('Y-m-d H:i:s'));
        }
    }

    public function getPayCenterChannelMerchantList(){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_pay_channel_merchant_list');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['pageSize'] = 499;
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        Log::write('充值中心-getPayCenterChannelMerchantList,url:'. $url);
        Log::write('充值中心-getPayCenterChannelMerchantList,params:'. print_r($params, true));
        $result = Curlrequest::post($url, $params);
        if($result === false){
            Log::write('充值中心-getPayCenterChannelMerchantList,请求出错！！！');
            return false;
        }
        $result = json_decode($result, true);
        if($result['code'] != 200){
            Log::write('充值中心-getPayCenterChannelMerchantList：'. print_r($result, true));
            return false;
        }

        return $result; 
    }

    public function updateChannelMerchantList(){
        $list = input_format($this->getPayCenterChannelMerchantList());
        $list = $list['data']['list'];
        foreach ($list as $key => $value) {
            unset($list[$key]['app_id']);
        }
        Log::write('充值中心-updateChannelMerchantList：'. print_r($list, true));
        $channelMerchant = Loader::model('PayCenterChannelMerchant');
        $channelMerchant->where("1=1")->delete();
        $status = $channelMerchant->insertAll($list, true);
        if ($status) {
            Log::info('充值中心渠道列表最后更新时间' . date('Y-m-d H:i:s'));
        }else{
            Log::error('更新充值中心渠道列表失败' . date('Y-m-d H:i:s'));
        }
    }


}