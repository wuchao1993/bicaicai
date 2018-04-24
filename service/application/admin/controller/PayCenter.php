<?php
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayCenter{

    public function merchantInfo(){
        $merchant = Loader::model('PayCenter', 'logic');
        $merchantInfo = $merchant->getInfo();
        return [
                'errorcode' => $merchant->errorcode,
                'message' => Config::get('errorcode')[$merchant->errorcode],
                'data' => output_format($merchantInfo) 
        ];
    }

    /**
     *设置商户信息
     */
    public function setMerchantInfo(Request $request){
        $params['merchantId'] = $request->param('merchantId');
        $params['signKey'] = $request->param('signKey');
        $params['notifyUrl'] = $request->param('notifyUrl');
        $params['callbackUrl'] = $request->param('callbackUrl');
        $merchant = Loader::model('PayCenter', 'logic');
        $merchant->setInfo($params);
        return [
                'errorcode' => $merchant->errorcode,
                'message' => Config::get('errorcode')[$merchant->errorcode]
        ];
    }
    
    /**
     *更新商户信息
     */
    public function editMerchantInfo(Request $request){
        $params['id'] = $request->param('id');
        $params['merchantId'] = $request->param('merchantId');
        $params['signKey'] = $request->param('signKey');
        $params['notifyUrl'] = $request->param('notifyUrl');
        $params['callbackUrl'] = $request->param('callbackUrl');
        $merchant = Loader::model('PayCenter', 'logic');
        $merchant->editInfo($params);
        return [
            'errorcode' => $merchant->errorcode,
            'message' => Config::get('errorcode')[$merchant->errorcode]
        ];
    }
     
    /**
     *获取支付类型列表
     */
    public function getPayTypeList(){
        $payType = Loader::model('PayCenter', 'logic');
        $result = $payType->getPayTypeList();
        return [
                'errorcode' => $payType->errorcode,
                'message' => Config::get('errorcode')[$payType->errorcode],
                'data' => output_format($result) 
        ];
    }


    /**
     *获取银行列表
     */
    public function getPayCenterBankList(){
        $merchant = Loader::model('PayCenter', 'logic');
        $list = $merchant->getPayCenterBankList();
        return [
                'errorcode' => $merchant->errorcode,
                'message' => Config::get('errorcode')[$merchant->errorcode],
                'data' => output_format($list) 
        ];
    }

    /**
     *更新银行列表
     */
    public function updateBankList(){
        $merchant = Loader::model('PayCenter', 'logic');
        $list = $merchant->updateBank();
        return [
                'errorcode' => $merchant->errorcode,
                'message' => Config::get('errorcode')[$merchant->errorcode],
                'data' => output_format($list) 
        ];
    }

    /**
     *获取渠道列表
     */
    public function getChannelList(Request $request){
        $params['page'] = $request->param('page') ? $request->param('page') : 1;
        $params['pageSize'] = $request->param('num') ? $request->param('num') : 10;
        $merchant = Loader::model('PayCenter', 'logic');
        $data = $merchant->getChannelList($params);
        return [
                'errorcode' => $merchant->errorcode,
                'message' => Config::get('errorcode')[$merchant->errorcode],
                'data' => [ 
                    'list' => output_format($data['list']),
                    'totalCount' => $data['count']
                ]
        ];
    }
    
    /**
     *获取渠道商户列表
     */
    public function getChannelMerchantList(Request $request){
        $params['page'] = $request->param('page');
        $params['num'] = $request->param('num');
        $params['status'] = $request->param('status');
        $params['payChannelId'] = $request->param('payChannelId');
        $params['userLevelId'] = $request->param('ulId');
        $payCenter = Loader::model('PayCenter', 'logic');
        $data = $payCenter->getChannelMerchantList($params);
        return [
                'errorcode' => $payCenter->errorcode,
                'message' => Config::get('errorcode')[$payCenter->errorcode],
                'data' => [
                    'list' => output_format($data['list']),
                    'totalCount' => $data['count']
                ]
        ]; 
    }
    
    /**
     *创建渠道商户
     */
    public function createChannelMerchant(Request $request){
        $params['payChannelId'] = $request->param('payChannelId');
        $params['payTypeId'] = $request->param('payTypeId');
        $params['redirectDomain'] = $request->param('redirectDomain');
        $params['account'] = $request->param('account');  //商户号
        $params['desc'] = $request->param('desc');  //账号描述
        $params['tag'] = $request->param('tag');
        $params['md5Key'] = $request->param('md5Key');
        $params['terminalId'] = $request->param('terminalId'); //终端号
        $params['limitAmount'] = $request->param('limitAmount'); //限额
        $params['rsaPriKey'] = $request->param('rsaPriKey');
        $params['rsaPublicKey'] = $request->param('rsaPublicKey');
        $userLevelId = $request->param('userLevelId/a');
        $payCenter = Loader::model('PayCenter', 'logic');
        $result = $payCenter->createChannelMerchant($params, $userLevelId);
        return [
                'errorcode' => $payCenter->errorcode,
                'message' => Config::get('errorcode')[$payCenter->errorcode]
        ];         
    }

    public function updateChannelMerchant(Request $request){
        $params['payChannelId'] = $request->param('payChannelId');
        $params['payTypeId'] = $request->param('payTypeId');
        $params['redirectDomain'] = $request->param('redirectDomain');
        $params['account'] = $request->param('account');  //商户号
        $params['desc'] = $request->param('desc');  //账号描述
        $params['md5Key'] = $request->param('md5Key');
        $params['terminalId'] = $request->param('terminalId'); //终端号
        $params['limitAmount'] = $request->param('limitAmount'); //限额
        $params['rsaPriKey'] = $request->param('rsaPriKey');
        $params['rsaPublicKey'] = $request->param('rsaPublicKey');
        $params['channelMerchantId'] = $request->param('channelMerchantId');
        $userLevelId = $request->param('userLevelId/a');
        $payCenter = Loader::model('PayCenter', 'logic');
        $result = $payCenter->updateChannelMerchant($params, $userLevelId);
        return [
                'errorcode' => $payCenter->errorcode,
                'message' => $result['message']
        ];
    }

    public function changeChannelMerchantStatus(Request $request){
        $status = $request->param('status');
        $params['channelMerchantId'] = $request->param('channelMerchantId');
        switch ($status) {
            case 0:
                $result = $this->disableChannelMerchant($params);
                break;
            case 1:
                $result = $this->enableChannelMerchant($params);
                break;
            case 2:
                $result = $this->deleteChannelMerchant($params);
                break;
        }
        return [
                'errorcode' => $result['errorcode'],
                'message' => $result['message']
        ]; 
    }

    private function enableChannelMerchant($params){
        $payCenter = Loader::model('PayCenter', 'logic');
        $payCenter->enableChannelMerchant($params);
        return [
                'errorcode' => $payCenter->errorcode,
                'message' => Config::get('errorcode')[$payCenter->errorcode]
        ]; 
    }

    private function disableChannelMerchant($params){
        $payCenter = Loader::model('PayCenter', 'logic');
        $result = $payCenter->disableChannelMerchant($params);
        return [
                'errorcode' => $payCenter->errorcode,
                'message' => Config::get('errorcode')[$payCenter->errorcode]
        ]; 
    }

    private function deleteChannelMerchant($params){
        $payCenter = Loader::model('PayCenter', 'logic');
        $payCenter->deleteChannelMerchant($params);        
        return [
                'errorcode' => $payCenter->errorcode,
                'message' => Config::get('errorcode')[$payCenter->errorcode]
        ]; 
    }

}
