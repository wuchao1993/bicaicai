<?php
namespace app\admin\controller;
use think\Loader;
use think\Request;
use think\Config;

class PayCenterMerchant{

    /**
     * 获取商户列表
     * @param Request $request
     * @return array
     */
    public function getList(Request $request){
        $params = $request->post();
        $params['page'] = $params['page'];
        $params['size'] = $params['num'];
        $params['tag'] = $request->param('ulId/a');
        unset($params['ulId']);
        $payCenter = Loader::model('PayCenterMerchant', 'logic');
        $data = $payCenter->getList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 创建商户
     * @param Request $request
     * @return array
     */
    public function add(Request $request){
        $params = $request->post();
        $params['tag'] = $params['ulId'];
        unset($params['ulId']);
        $payCenter = Loader::model('PayCenterMerchant', 'logic');
        $data = $payCenter->add($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 获取商户详情
     */
    public function getDetail(Request $request){
        $params['channelMerchantId'] = $request->param('merchantId');
        $payCenter = Loader::model('PayCenterMerchant', 'logic');
        $data = $payCenter->getDetail($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     *修改渠道商户
     */
    public function edit(Request $request){
        $params['merchantId'] = $request->param('merchantId');
        $params['payChannelId'] = $request->param('payChannelId');
        $params['payTypeId'] = $request->param('payTypeId');
        $params['redirectDomain'] = $request->param('redirectDomain');
        $params['account'] = $request->param('account');  //商户号
        $params['desc'] = $request->param('desc');  //账号描述
        $params['tag'] = $request->param('ulId/a');
        $params['md5Key'] = $request->param('md5Key');
        $params['terminalId'] = $request->param('terminalId'); //终端号
        $params['limitAmount'] = $request->param('limitAmount'); //限额
        $params['minRechargeAmount'] = $request->param('minRechargeAmount'); //最小充值
        $params['maxRechargeAmount'] = $request->param('maxRechargeAmount'); //最大充值
        $params['rsaPriKey'] = $request->param('rsaPriKey');
        $params['rsaPublicKey'] = $request->param('rsaPublicKey');
        $params['signType'] = $request->param('signType');
        $params['orderIndex'] = $request->param('orderIndex');
        $payCenter = Loader::model('PayCenterMerchant', 'logic');
        $data = $payCenter->edit($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }


    /**
     * 删除渠道商户详情
     * @param Request $request
     * @return array
     */
    public function delete(Request $request){
        $params['merchantId'] = $request->param('merchantId');
        $payCenter = Loader::model('PayCenterMerchant', 'logic');
        $data = $payCenter->delete($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 禁用/启用
     * @param Request $request
     * @return array
     */
    public function changeStatus(Request $request){
        $params['merchantId'] = $request->param('merchantId');
        $params['status'] = $request->param('status');
        $payCenter = Loader::model('PayCenterMerchant', 'logic');
        $data = $payCenter->changeStatus($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

}