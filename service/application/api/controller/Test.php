<?php

namespace app\api\controller;

use curl\Curlrequest;
use email\Email;
use think\Config;

class Test
{

    public function testEmail(){
        $message = "test";
        $sendInfo = [
            'subject' => 'this is a test !!',
            'body' => $message,
            'alt_body' => $message
        ];
        Email::send($sendInfo);
    }

    public function index()
    {
        $apiUrl = "http://" . $_SERVER["HTTP_HOST"] . "/api/";
        $apiInfo = $this->_bindCard();
        $curlRequest = new Curlrequest();
        $result = $curlRequest->post($apiUrl . $apiInfo['action'], $apiInfo['params']);
        print_r($result);
    }


    private function _pay(){
        $apiInfo = [
            'action' => "Recharge/pay",
            'params' => [
                'amount' => 1,
                'categoryId' => 5,
                'bankId' => 1
            ]
        ];

        return $apiInfo;
    }


    private function _bindRealInfo(){
        $apiInfo = [
            'action' => "UserConfig/bindRealInfo",
            'params' => [
                'realName' => "令狐冲",
                'fundsPassword' => 'aaaaaa222',
            ]
        ];

        return $apiInfo;
    }


    private function _bindCard(){
        $apiInfo = [
            'action' => "UserConfig/bindCard",
            'params' => [
                'id' => 1,
                'address' => '嘿嘿嘿支行',
                'cardNumber' => '6222888888888888',
                'fundsPassword' => 'aaaaaa222'
            ]
        ];

        return $apiInfo;
    }


    private function _getBanks(){
        $apiInfo = [
            'action' => "User/getBanks",
            'params' => [

            ]
        ];

        return $apiInfo;
    }


    private function _withdraw(){
        $apiInfo = [
            'action' => "Withdraw/applyWithdraw",
            'params' => [
                'userBankId' => 3885,
                'amount' => 10,
                'fundsPassword' => 'aaaaaa222'
            ]
        ];

        return $apiInfo;
    }


    private function _getCompanyRechargeTypeList(){
        $apiInfo = [
            'action' => "Bank/getCompanyRechargeTypeList",
            'params' => [

            ]
        ];

        return $apiInfo;
    }


}