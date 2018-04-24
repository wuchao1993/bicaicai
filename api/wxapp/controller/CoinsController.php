<?php

namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use wxapp\aes\WXBizDataCrypt;

class CoinsController extends RestBaseController
{
    // 获取用户信息
    public function getBtcInfo()
    {

    	$apiKey = "9be859ca-2534-47c0-aa41-b374a41dd3bc";
    	$secretKey = "650FE99264644F3FCF9B1F4010570564";
    	$path = EXTEND_PATH. '/okcoin';
    	import('OKCoin',$path);
    	$client = new \OKCoin(new \OKCoin_ApiKeyAuthentication($apiKey, $secretKey));
		$params = array('symbol' => 'btc_usd');
		$result = $client -> tickerApi($params);
		$this->success("获取成功", $result->ticker->last);

    }

}
