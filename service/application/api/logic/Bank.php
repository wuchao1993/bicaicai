<?php

namespace app\api\logic;

use think\Loader;
use think\Config;
use curl\Curlrequest;

class Bank{

    public $errorcode = EC_SUCCESS;

    public function getBankList(){

        $condition = [
            'bank_status' => Config::get('status.bank_status')['normal'],
            'bank_id' => ['lt', 1000]
        ];

        return Loader::model('Bank')->where($condition)->select();
    }

    public function getPayCenterBankList(){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_bank_list_url');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['pageSize'] = 100;
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result['data']['list'];     
    }  

    public function getMap(){
        $condition = [
            'bank_status' => Config::get('status.bank_status')['normal'],
            'bank_id' => ['lt', 1000]
        ];

        $result = Loader::model('Bank')->where($condition)->column('bank_name', 'bank_id');
        return $result;
    }


    public function getList(){
        $condition = [
            'bank_status' => Config::get('status.bank_status')['normal'],
            'bank_id' => ['lt', 1000]
        ];

        return Loader::model('Bank')->where($condition)->column('bank_id,bank_name,bank_image_mobile,bank_code', 'bank_id');
    }

    public function getInfoById($id){
        $condition = [
            'bank_status' => Config::get('status.bank_status')['normal'],
            'bank_id' => ['eq', $id]
        ];
        return Loader::model('Bank')->where($condition)->find();
    }

}