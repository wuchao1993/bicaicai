<?php

namespace app\pay\controller;

use think\Loader;
use think\Config;
use think\Log;
use think\Request;
use think\Hook;

class Notify
{

    public function index(Request $request)
    {
        Log::write("回调请求的参数：" . print_r($request->param(), true));
        $params['outTradeNo'] = $request->param('outTradeNo');
        $params['payCenterOrderNo'] = $request->param('payCenterOrderNo');
        $params['totalFee'] = $request->param('totalFee');
        $params['tradeStatus'] = $request->param('tradeStatus');
        $params['channelTradeNo'] = $request->param('channelTradeNo');
        $params['payTime'] = $request->param('payTime');
        $params['tradeDesc'] = $request->param('tradeDesc');
        $sign = $request->param('sign');
        $notify = Loader::model('Notify', 'logic');
        $response = $notify->handleOrder($params, $sign);
        if ($response) {
            echo 'success';
        } else {
            echo 'error';
        }
    }


}