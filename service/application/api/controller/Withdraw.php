<?php

namespace app\api\controller;

use think\Loader;
use think\Config;
use think\Request;
use think\Hook;

class Withdraw {

    public function __construct() {}

    /**
     * 申请提现
     * @param Request $request
     * @return mixed
     */
    public function applyWithdraw(Request $request){
        Hook::listen('auth_check');
        $userBankId = $request->param('userBankId');
        $amount = $request->param('amount');
        $fundsPassword = $request->param('fundsPassword');
        $withdrawLogic = Loader::model('common/Withdraw', 'logic');
        $response = $withdrawLogic->withdraw($userBankId, $amount, $fundsPassword);

        if(is_array($response)){
            return $response;
        }else{
            return [
                'errorcode' => $withdrawLogic->errorcode,
                'message'   => Config::get('errorcode')[$withdrawLogic->errorcode]
            ];
        }
    }
    
    /**
     * 特殊代理提现
     * @param Request $request
     * @return mixed
     */
    public function specialAgentWithdraw(Request $request){
        Hook::listen ( 'auth_check' );
        $amount = $request->param('amount');
        $withdrawLogic = Loader::model('common/Withdraw', 'logic');
        $response = $withdrawLogic->specialAgentWithdraw($amount);

        if(is_array($response)){
            return $response;
        }else{
            return [
                'errorcode' => $withdrawLogic->errorcode,
                'message'   => Config::get('errorcode')[$withdrawLogic->errorcode]
            ];
        }
    }


    public function getWithdrawCheckList(Request $request){
        Hook::listen ( 'auth_check' );
        $params = $request->post();
        $withdrawLogic = Loader::model('Withdraw', 'logic');
        $result = $withdrawLogic->getWithdrawCheckList($params);

        return send_response($result, $withdrawLogic->errorcode);
    }


    public function getWithdrawConfig(Request $request){
        Hook::listen ( 'auth_check' );
        $params = $request->post();
        $withdrawLogic = Loader::model('Withdraw', 'logic');
        $result = $withdrawLogic->getWithdrawConfig($params);

        return send_response($result, $withdrawLogic->errorcode);
    }
}