<?php

namespace app\api\controller;

use think\Loader;
use think\Config;
use think\Request;
use think\Hook;

class Recharge {

    public function __construct() {
        
    }

    /**
     * 充值预支付接口
     * @param Request $request
     * @return array
     */
    public function pay(Request $request) {
        Hook::listen('auth_check');
        //验证用户级别
        if($this->_checkUserLevel() === false) {
            return [
                'errorcode' => EC_TEST_USER_CAN_NOT_RECHARGE,
                'message'   => Config::get('errorcode')[EC_TEST_USER_CAN_NOT_RECHARGE],
            ];
        }

        $amount     = $request->param('amount');
        $payTypeId = $request->param('payTypeId');
        $bankId     = $request->param('bankId');

        $rechargeLogic = Loader::model('Recharge', 'logic');

        $payPlatformCategoryFriend = [
            PAY_PLATFORM_CATEGORY_ALIPAY_FRIEND,
            PAY_PLATFORM_CATEGORY_WEIXIN_FRIEND,
            PAY_PLATFORM_CATEGORY_QQ_WALLET_FRIEND,
            PAY_PLATFORM_CATEGORY_JD_WALLET_FRIEND,
            PAY_PLATFORM_CATEGORY_BD_WALLET_FRIEND,
            PAY_PLATFORM_CATEGORY_CENTRALIZED_SCAN_CODE,
        ];

        //好友支付
        if(in_array($payTypeId, $payPlatformCategoryFriend)) {
            $response = $rechargeLogic->getFriendQrCode($payTypeId);
        } else {
            $response = $rechargeLogic->pay($amount, $payTypeId, $bankId);
        }

        return $response;

    }

    /**
     * 特殊代理充值预支付接口
     * @param Request $request
     * @return array
     */
    public function specialAgentPay(Request $request){
        Hook::listen('auth_check');
        $amount     = $request->param('amount');

        $rechargeLogic = Loader::model('Recharge', 'logic');
        $response = $rechargeLogic->specialAgentPay($amount);
        return $response;
    }


    /**
     * 获取好友支付帐号
     * @param Request $request
     * @return mixed
     */
    public function getFriendsPayAccount(Request $request) {
        Hook::listen('auth_check');
        $payTypeId = $request->param('payTypeId');
        $rechargeLogic = Loader::model('Recharge', 'logic');

        return $rechargeLogic->getFriendQrCode($payTypeId);
    }


    /**
     * 获取充值类型列表
     * @return array
     */
    public function getRechargeTypeList() {
        Hook::listen('auth_check');
        $rechargeLogic = Loader::model('Recharge', 'logic');
        $data          = $rechargeLogic->getRechargeTypeList();

        return [
            'errorcode' => $rechargeLogic->errorcode,
            'message'   => Config::get('errorcode')[$rechargeLogic->errorcode],
            'data'      => $data,
        ];
    }


    private function _checkUserLevel() {
        $userInfo = Loader::model('User', 'logic')->getInfoByUid(USER_ID);
        $userName = $userInfo['user_name'];
        if(is_test_user($userName)) {
            return false;
        }
    }


    /**
     * 好友支付填单接口
     * @param Request $request
     * @return mixed
     */
    public function friendPay(Request $request) {
        Hook::listen('auth_check');
        $params        = $request->post();
        $rechargeLogic = Loader::model('Recharge', 'logic');

        return $rechargeLogic->friendPay($params);
    }


    /**
     * 公司入款填单
     * @param Request $request
     * @return mixed
     */
    public function companyRecharge(Request $request) {
        Hook::listen('auth_check');
        $params        = $request->post();
        $rechargeLogic = Loader::model('Recharge', 'logic');

        return $rechargeLogic->addCompanyRecharge($params);
    }


    public function searchOrderState(Request $request) {
        Hook::listen('auth_check');
        $orderNo = $request->param('orderNo');
        $rechargeLogic = Loader::model('Recharge', 'logic');
        $rechargeLogic->searchOrderState($orderNo);

        return [
            'errorcode' => $rechargeLogic->errorcode,
            'message'   => Config::get('errorcode')[$rechargeLogic->errorcode]
        ];
    }


    public function getRechargeDetail(Request $request) {
        Hook::listen('auth_check');
        $orderNo = $request->param('orderNo');
        $rechargeLogic = Loader::model('Recharge', 'logic');
        $response = $rechargeLogic->getRechargeDetail($orderNo);

        return [
            'errorcode' => $rechargeLogic->errorcode,
            'message'   => Config::get('errorcode')[$rechargeLogic->errorcode],
            'data'      => $response
        ];
    }

    public function notify(Request $request){
        $params['outTradeNo']       = $request->param('outTradeNo');
        $params['payCenterOrderNo'] = $request->param('payCenterOrderNo');
        $params['totalFee']         = $request->param('totalFee');
        $params['tradeStatus']      = $request->param('tradeStatus');
        $params['channelTradeNo']   = $request->param('channelTradeNo');
        $params['payTime']          = $request->param('payTime');
        $params['tradeDesc']        = $request->param('tradeDesc');
        $sign                       = $request->param('sign');
        $rechargeLogic = Loader::model('Recharge', 'logic');
        $response = $rechargeLogic->handleOrder($params, $sign);
        if($response) {
            echo 'success';
        }else{
            echo 'error';
        }
    }

    public function callback(){
        echo 'success';
    }

}