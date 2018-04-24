<?php

/**
 * 用户金流记录控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class UserAccountRecord {

    /**
     * 获取金流列表
     *
     * @param Request $request
     * @return array
     */
    public function getAccountList(Request $request) {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );

        if ($request->param ( 'username' ) != '') {
            $params ['user_name'] = $request->param ( 'username' );
        }
        if ($request->param ( 'startDate' ) != '') {
            $params ['start_date'] = date ( 'Y-m-d 00:00:00', strtotime ( $request->param ( 'startDate' ) ) );
        }
        if ($request->param ( 'endDate' ) != '') {
            $params ['end_date'] = date ( 'Y-m-d 23:59:59', strtotime ( $request->param ( 'endDate' ) ) );
        }
        if ($request->param ( 'dateType' ) != '') {
            $params ['date_type'] = $request->param ( 'dateType' );
        }
        if ($request->param ( 'actionType' ) != '') {
            $params ['uar_action_type'] = $request->param ( 'actionType' );
        }
        if ($request->param ( 'transactionType/a' ) != '') {
            $params ['uar_transaction_type'] = $request->param ( 'transactionType/a' );
        }

        $userAccountRecordLogic = Loader::model ( 'UserAccountRecord', 'logic' );
        $accountList = $userAccountRecordLogic->getAccountListColdData ( $params );
        foreach ( $accountList ['list'] as &$info ) {
            $info = $this->_packAccountInfo ( $info );
        }

        return [
            'errorcode' => $userAccountRecordLogic->errorcode,
            'message' => Config::get ( 'errorcode' ) [$userAccountRecordLogic->errorcode],
            'data' => $accountList
        ];
    }
    
    /**
     * 获取金流类型
     * 
     * @param Request $request            
     * @return array
     */
    public function getTransactionTypeList(Request $request) {
        $userAccountRecordLogic = Loader::model ( 'UserAccountRecord', 'logic' );
        
        $data = array();
        $accontActionTypeNameForClient = Config::get ( 'status.account_record_action_type_name_for_client' );
        foreach ($accontActionTypeNameForClient as $key => $value) {
            $temp = array (
                    'value' => $key,
                    'label' =>  Config::get ( 'status.account_record_transaction_type_name' )[$key]
            );
            $data['all'][] = $temp;
            if ($value == '盈利') {
                $value = 'deposit';
            }else{
                $value = 'fetch';
            }
            $data[$value][] = $temp;
        }

        return [ 
                'errorcode' => $userAccountRecordLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$userAccountRecordLogic->errorcode],
                'data' => output_format ( array_merge($data) ) 
        ];
    }
    private function _packAccountInfo($info) {
        return [ 
                'username' => $info ['user_name'],
                'actionType' => Config::get ( 'status.account_record_action_type_name_for_client' ) [$info ['uar_transaction_type']],
                'transactionType' => Config::get ( 'status.account_record_transaction_type_name' ) [$info ['uar_transaction_type']],
                'amount' => $info ['uar_amount'],
                'beforeBalance' => $info ['uar_before_balance'],
                'afterBalance' => $info ['uar_after_balance'],
                'createtime' => $info ['uar_createtime'],
                'finishtime' => $info ['uar_finishtime'],
                'remark' => $info ['uar_remark'] 
        ];
    }
}
