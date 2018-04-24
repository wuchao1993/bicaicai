<?php

namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayAccount {
    public function getPayAccountList(Request $request) {
        $page = $request->param ( 'page' );
        $num = $request->param ( 'num' );
        $status = $request->param ( 'status' );
        $ul_id = $request->param ( 'ulId' );
        
        $payAccountLogic = Loader::model ( 'PayAccount', 'logic' );
        $data = $payAccountLogic->getList ( $page, $num, $status, $ul_id );
        $responseData = [ ];
        foreach ( $data ['list'] as $key => $info ) {
            $responseData [$key] = $this->_packPayAccountInfo ( $info );
        }
        return [ 
                'errorcode' => $payAccountLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$payAccountLogic->errorcode],
                'data' => [ 
                        'list' => $responseData,
                        'totalCount' => $data ['count'] 
                ] 
        ];
    }
    public function getPayAccountInfo(Request $request) {
        $payAccountId = $request->param ( 'id' );
        $payAccountLogic = Loader::model ( 'PayAccount', 'logic' );
        $info = $payAccountLogic->getInfo ( $payAccountId );
        
        return [ 
                'errorcode' => $payAccountLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$payAccountLogic->errorcode],
                'data' => $this->_packPayAccountInfo ( $info ) 
        ];
    }
    private function _packPayAccountInfo($info) {
        return [ 
                'id' => $info ['pa_id'],
                'bankId' => $info ['bank_id'],
                'bankName' => $info ['bank_name'],
                'bankAddr' => $info ['pa_bank_addr'],
                'accountName' => $info ['pa_collection_user_name'],
                'accountNumber' => $info ['pa_collection_account'],
                'codeUrl' => $info ['pa_code_url'],
                'limitAmount' => $info ['pa_limit_amount'],
                'rechargeAmount' => $info ['pa_recharge_amount'],
                'levelList' => $info ['levelList'],
                'sort' => $info ['pa_sort'],
                'remark' => $info ['pa_remark'],
                'status' => $info ['pa_status'],
                'createtime' => $info ['pa_createtime'],
                'modifytime' => $info ['pa_modifytime'] 
        ];
    }
    public function editPayAccount(Request $request) {
        $payAccountid = $request->param ( 'id' );
        $ulId = $request->param ( 'ulId/a' );

        $payAccountInfo = [ 
                'bank_id' => $request->param ( 'bankId' ),
                'pa_bank_addr' => $request->param ( 'bankAddr' ),
                'pa_collection_user_name' => $request->param ( 'accountName' ),
                'pa_collection_account' => $request->param ( 'accountNumber' ),
                'pa_code_url' => $request->param ( 'codeUrl' ),
                'pa_limit_amount' => $request->param ( 'limitAmount' ),
                'pa_sort' => $request->param ( 'sort' ),
                'pa_remark' => $request->param ( 'remark' ),
                'pa_status' => $request->param ( 'status' ) 
        ];
        
        if ($payAccountid) {
            $payAccountInfo ['pa_id'] = $payAccountid;
            $payAccountInfo ['pa_modifytime'] = current_datetime ();
        } else {
            $payAccountInfo ['pa_createtime'] = current_datetime ();
            $payAccountInfo ['pa_modifytime'] = current_datetime ();
        }
        
        $payAccountLogic = Loader::model ( 'PayAccount', 'logic' );
        $payAccountLogic->editInfo ( $payAccountInfo, $ulId );
        
        return [ 
                'errorcode' => $payAccountLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$payAccountLogic->errorcode] 
        ];
    }
    
    /**
     * 修改状态
     * 
     * @param
     *            $params
     * @return array
     */
    public function changeStatus(Request $request) {
        $params ['pa_id'] = $request->param ( 'id' );
        $params ['pa_status'] = $request->param ( 'status' );
        
        $payAccountLogic = Loader::model ( 'PayAccount', 'logic' );
        $result = $payAccountLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $payAccountLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$payAccountLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除
     *
     * @param
     *            $params
     * @return array
     */
    public function delPayAccount(Request $request) {
        $params ['pa_id'] = $request->param ( 'id' );
        
        $payAccountLogic = Loader::model ( 'PayAccount', 'logic' );
        $result = $payAccountLogic->del ( $params );
        
        return [
                'errorcode' => $payAccountLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$payAccountLogic->errorcode],
                'data' => $result
        ];
    }
}