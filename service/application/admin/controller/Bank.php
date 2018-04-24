<?php

namespace app\admin\controller;

use think\Loader;
use think\Config;
use think\Request;

class Bank {
    
    /**
     *
     * @param Request $request            
     * @return array 获取银行列表
     */
    public function getBankList(Request $request) {
        $page = $request->param('page');
        $num = $request->param('num');
        
        $params = [ ];
        if($request->param('name') != ''){
            $params['bank_name'] = $request->param('name');
        }
        
        $bankLogic = Loader::model('Bank', 'logic');
        $data = $bankLogic->getList($page, $num, $params);
        $responseList = [ ];
        foreach($data['list'] as $key => $item){
            $responseList[$key] = $this->_packBankInfo ( $item );
        }
        return [ 
                'errorcode' => $bankLogic->errorcode,
                'message' => Config::get('errorcode')[$bankLogic->errorcode],
                'data' => [ 
                        'list' => $responseList,
                        'totalCount' => $data['count'] 
                ] 
        ];
    }

    public function getPayCenterBankList(Request $request){
        $params['page'] = $request->param('page');
        $params['num'] = $request->param('num');
        $bank = Loader::model('Bank', 'logic');
        $result = $bank->getPayCenterBankList($params);
        return [ 
                'errorcode' => $result['code'],
                'message' => $result['message'],
                'data' => [ 
                        'list' => $result['data']['list'],
                        'totalCount' => $result['data']['total']
                ] 
        ];

    }

    public function payCenterBankList($params){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_bank_list_url');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['pageSize'] = $params['num'];
        $params['page'] = $params['page'] ? $params['page'] : 1;
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result['data']['list'];            
    }

    
    /**
     *
     * @param Request $request            
     * @return array 获取银行详情
     */
    public function getBankInfo(Request $request) {
        $bankId = $request->param ( 'id' );
        $bankLogic = Loader::model ( 'Bank', 'logic' );
        $data = $bankLogic->getInfo ( $bankId );
        
        return [ 
                'errorcode' => $bankLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$bankLogic->errorcode],
                'data' => $this->_packBankInfo ( $data ) 
        ];
    }
    private function _packBankInfo($info) {
        return [ 
                'id' => $info ['bank_id'],
                'name' => $info ['bank_name'],
                'imagePc' => $info ['bank_image_pc'],
                'imageMobile' => $info ['bank_image_mobile'],
                'code' => $info ['bank_code'],
                'status' => $info ['bank_status'],
                'createtime' => $info ['bank_createtime'] 
        ];
    }
    
    /**
     *
     * @param Request $request            
     * @return array 编辑银行信息
     */
    public function editBankInfo(Request $request) {
        $bankId = $request->param ( 'id' );
        $bankInfo = [ 
                'bank_name' => $request->param ( 'name' ),
                'bank_image_pc' => $request->param ( 'imagePc' ),
                'bank_image_mobile' => $request->param ( 'imageMobile' ),
                'bank_code' => $request->param ( 'code' ),
                'bank_status' => $request->param ( 'status' ) 
        ];
        
        $bankLogic = Loader::model ( 'Bank', 'logic' );
        if ($bankId > 0) {
            $result = $bankLogic->editInfo ( $bankId, $bankInfo );
        } else {
            $bankInfo ['bank_createtime'] = current_datetime ();
            $result = $bankLogic->add ( $bankInfo );
        }
        
        return [ 
                'errorcode' => $bankLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$bankLogic->errorcode],
                'data' => $result 
        ];
    }
}