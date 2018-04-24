<?php
namespace app\admin\controller;
use think\Loader;
use think\Request;
use think\Config;

class PayCenterChannel{

    /**
     * 获取可用渠道列表
     */
    public function getList(Request $request){
        $params = [];
        $payCenter = Loader::model('PayCenterChannel', 'logic');
        $data = $payCenter->getList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 渠道可用支付类型列表
     */
    public function getUsablePayTypeList(Request $request){
        $params['channelId'] = $request->param('channelId');
        $payCenter = Loader::model('PayCenterChannel', 'logic');
        $data = $payCenter->getUsablePayTypeList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

}