<?php
namespace app\admin\logic;

use think\Cache;
use think\Config;

class PayCenterBank extends PayCenterBase{

    public $errorcode = EC_AD_SUCCESS;
    public $errorMessage = "";

    public function getList(){
        $cacheName = Config::get('cache_option.prefix')['pay_center_bank_list'];
        $response = Cache::get($cacheName);
        if($response){
            return $response;
        }
        $apiUrl = Config::get('pay.get_bank_list');
        $result =  call_pay_center_api($apiUrl);
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        $response = $this->_buildResponse($result['data']);
        Cache::set($cacheName, $response, 5*60);
        return $response;
    }

}