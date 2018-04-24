<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

class IndexController extends RestBaseController
{
    // api 首页
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
