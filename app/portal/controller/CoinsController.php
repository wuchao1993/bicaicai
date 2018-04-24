<?php
namespace app\portal\controller;

use cmf\controller\HomeBaseController;


class CoinsController extends HomeBaseController
{

    public function index()
    {
        $apiKey = "9be859ca-2534-47c0-aa41-b374a41dd3bc";
        $secretKey = "650FE99264644F3FCF9B1F4010570564";
        $path = EXTEND_PATH. '/okcoin';
        import('OKCoin',$path);
        $client = new \OKCoin(new \OKCoin_ApiKeyAuthentication($apiKey, $secretKey));
    //获取OKCoin行情（盘口数据）
    $params = array('symbol' => 'btc_usd');
    $result = $client -> tickerApi($params);
    return $result;
    }
}
