<?php

namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayPlatform
{

    /**
     * @param Request $request
     * @return array
     * 获取支付平台列表
     */
    public function getPayPlatformList(Request $request)
    {
        $type = $request->param('type');
        $status = $request->param('status');
        $ulId= $request->param('ulId');
        $page = $request->param('page');
        $num = $request->param('num');
        $channelId =$request->param('channelId');

        $payPlatformLogic = Loader::model('PayPlatform', 'logic');
        $data = $payPlatformLogic->getList($status, $ulId, $page, $num, $channelId,$type);
        $responseList = [];
        foreach ($data['list'] as $key => $info) {
            if($info['pp_redict_domain']){
                $info['notify'] = 'http://'.$info['pp_redict_domain'].'/notifyUrl/id/'.$info['pp_id'].'.html';
            }else{
                $info['notify'] = '请及时配置商城域名';
            }
            $responseList[$key] = $this->_packPlatformInfo($info);
        }

        return [
            'errorcode' => $payPlatformLogic->errorcode,
            'message' => Config::get('errorcode')[$payPlatformLogic->errorcode],
            'data' => [
                'list' => $responseList,
                'totalCount' => $data['count']
            ],
        ];
    }
    /**
     * @param Request $request
     * @return array
     * 获取支付平台详情
     */
    public function getPayPlatformInfo(Request $request)
    {
        $id = $request->param('id');
        $payPlatformLogic = Loader::model('PayPlatform', 'logic');
        $data = $payPlatformLogic->getInfo($id);

        return [
            'errorcode' => $payPlatformLogic->errorcode,
            'message' => Config::get('errorcode')[$payPlatformLogic->errorcode],
            'data' => $this->_packPlatformInfo($data),
        ];
    }

    private function _packPlatformInfo($info)
    {
        return [
            'id' => $info['pp_id'],
            'channelId' => $info['pay_type_id'],
            'channelName' => $info['pay_type_name'],
            'categoryId' => $info['pp_category_id'],
            'categoryName' => Config::get('status.pay_category_type_name')[$info['pp_category_id']],
            'redictDomain' => $info['pp_redict_domain'],
            'accountNo' => $info['pp_account_no'],
            'accountKey' => '',
            'terminalId' => $info['pp_terminal_id'],
            'limitAmount' => $info['pp_limit_amount'],
            'rechargeAmount' => $info['pp_recharge_amount'],
            'rechargeCount' => $info['pp_recharge_count'],
            'rsaPubkey' => $info['pp_rsa_pub_key'],
            'rsaPrikey' => $info['pp_rsa_pri_key'],
            'sort' => $info['pp_sort'],
            'status' => $info['pp_status'],
            'levelList' => $info['levelList'],
            'notify' => $info['notify'],
            'minpaymoney' => $info['pp_min_pay_money'],
            'maxpaymoney' => $info['pp_max_pay_money'],
            'payisfloat' => $info['pp_pay_is_float'],
        ];
    }


    /**
     * @param Request $request
     * @return array
     * 编辑渠道信息
     */
    public function editPayPlatformInfo(Request $request)
    {
        $id    = $request->param('id');
        $file  = $request->param('file');
        $filePassword  = $request->param('filepassword');
        $rsaPrikey     = $request->param('rsaPrikey');
        if($file){
            $rsaPrikey    = $this->getPrivateKey($file, $filePassword);
        }
        if(intval($request->param('minpaymoney')) > intval($request->param('maxpaymoney'))){
            return [
                'errorcode' => MIN_GT_MAX,
                'message' => Config::get('errorcode')[MIN_GT_MAX]
            ];
        }
        $payPlatformInfo = [
            'pay_type_id' => $request->param('channelId'),
            'pp_category_id' => $request->param('categoryId'),
            'pp_redict_domain' => $request->param('redictDomain'),
            'pp_account_no' => $request->param('accountNo'),
            'pp_terminal_id' => $request->param('terminalId'),
            'pp_limit_amount' => $request->param('limitAmount'),
            'pp_rsa_pub_key' => $request->param('rsaPubkey'),
            'pp_rsa_pri_key' => $rsaPrikey,
            'pp_min_pay_money' => $request->param('minpaymoney'),
            'pp_max_pay_money' => $request->param('maxpaymoney'),
            'pp_pay_is_float' => $request->param('payisfloat'),
            'pp_sort' => $request->param('sort'),
            'pp_status' => $request->param('status'),
            'ulId' => $request->param('ulId/a'),
        ];

        if($request->param('accountKey')){
            $accountKey = think_encrypt($request->param('accountKey'),Config::get('DATA_AUTH_KEY'));
            $payPlatformInfo['pp_account_key'] = $accountKey;
        }
        if($request->param('notifykey')){
            $payPlatformInfo['pp_notify_key'] = $request->param('notifykey');
        }

        $payPlatformLogic = Loader::model('PayPlatform', 'logic');

        if($id){
            $payPlatformInfo['pp_id'] = $id;
            $payPlatformInfo['pp_modifytime'] = current_datetime();
        }else{
            $payPlatformInfo['pp_createtime'] = current_datetime();
            $payPlatformInfo['pp_modifytime'] = current_datetime();
        }

        $result = $payPlatformLogic->editInfo($payPlatformInfo);
        return [
            'errorcode' => $payPlatformLogic->errorcode,
            'message' => Config::get('errorcode')[$payPlatformLogic->errorcode]
        ];
    }

    public function getPrivateKey($cert_path,$file_pswd) {
        $pkcs12 = file_get_contents ( $cert_path );
        openssl_pkcs12_read ( $pkcs12, $certs, $file_pswd);
        return $certs ['pkey'];
    }

    /**
     * @param Request $request
     * @return array
     * 获取支付平台搜索列表
     */
    public function getPayPlatformSearchList(Request $request)
    {
        $payPlatformLogic = Loader::model('PayPlatform', 'logic');
        $data = $payPlatformLogic->getPayPlatformSearchList();
        $responseList = [];
        foreach ($data as $key => $info) {
            $responseList[$key] = $this->_packPlatformSearchInfo($info);
        }
        
        return [
                'errorcode' => $payPlatformLogic->errorcode,
                'message' => Config::get('errorcode')[$payPlatformLogic->errorcode],
                'data' => $responseList,
        ];
    }
    
    /**
     * 删除
     * @param Request $request
     * @return array
     */
    public function delPlatform(Request $request) {
        $params['pp_id']		= $request->param('id');
        $params['pp_status']    = 2;
        $payPlatformLogic = Loader::model('PayPlatform', 'logic');
        $result  = $payPlatformLogic->changeStatus($params);
        
        return [
                'errorcode' => $payPlatformLogic->errorcode,
                'message'   => Config::get('errorcode')[$payPlatformLogic->errorcode],
                'data'      => $result,
        ];
    }
    
    private function _packPlatformSearchInfo($info)
    {
        return [
                'id' => $info['pp_id'],
                'value' => $info['pay_type_name'].'_'.Config::get ( 'status.pay_category_type_name' ) [$info ['pp_category_id']],
                'accountNo' => $info['pp_account_no'],
        ];
    }
    
    /**
     * 修改状态
     * @param $params
     * @return array
     */
    public function changeStatus(Request $request)
    {
        $params['pp_id']		= $request->param('id');
        $params['pp_status']	= $request->param('status');
        
        $payPlatformLogic= Loader::model('PayPlatform', 'logic');
        $result  = $payPlatformLogic->changeStatus($params);
        
        return [
                'errorcode' => $payPlatformLogic->errorcode,
                'message'   => Config::get('errorcode')[$payPlatformLogic->errorcode],
                'data'      => $result,
        ];
    }
}