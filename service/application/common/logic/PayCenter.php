<?php

namespace app\common\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;
class PayCenter extends Model{

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
            $result = json_decode(Curlrequest::post($apiUrl, $params), true);
            $list = array();
            foreach ($result['data']['list'] as &$value) {
                $list[$value['payTypeId']] = $value['shortName'];
            }
            Cache::set($cacheName, $list, 2*60);
            return $list;
        }    
    }

}