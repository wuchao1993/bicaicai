<?php
namespace app\pay\controller;

use think\Request;
use think\Config;
use think\Hook;
use think\Loader;

class Index
{
    /**
     * @param Request $request
     * @return array
     */
    public function getMerchantGroup(Request $request)  {
        Hook::listen('auth_check');
        $merchantLogic = Loader::model('Merchant', 'logic');
        $data = $merchantLogic->getMerchantGroup();

        return send_response($data, $merchantLogic->errorcode);
    }


    public function onlineRecharge(Request $request){
        Hook::listen('auth_check');
        $merchantLogic = Loader::model('Recharge', 'logic');
        $data = $merchantLogic->onlineRecharge($request->post());

        return send_response($data, $merchantLogic->errorcode, $merchantLogic->errorMessage);
    }


    public function friendRecharge(Request $request){
        Hook::listen('auth_check');
        $merchantLogic = Loader::model('Recharge', 'logic');
        $data = $merchantLogic->friendRecharge($request->post());

        return send_response($data, $merchantLogic->errorcode, $merchantLogic->errorMessage);
    }


    public function companyRecharge(Request $request){
        Hook::listen('auth_check');
        $merchantLogic = Loader::model('Recharge', 'logic');
        $data = $merchantLogic->companyRecharge($request->post());

        return send_response($data, $merchantLogic->errorcode, $merchantLogic->errorMessage);
    }

}