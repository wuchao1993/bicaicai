<?php

namespace app\admin\controller;

use curl\Curlrequest;
use PHPExcel;
use PHPExcel_Writer_Excel5;
use think\Loader;
use think\log;

class Test
{

    private $pids = [];

    public function index()
    {
        $phpExcel = new PHPExcel();

        print_r($phpExcel);exit;
        echo $_SERVER["HTTP_HOST"];
        $apiUrl = "http://" . $_SERVER["HTTP_HOST"] . "/admin/";
        $apiInfo = $this->_activeQuery();
//        $apiInfo = $this->_memberLogin();
        $curlRequest = new Curlrequest();
        $result = $curlRequest->post($apiUrl . $apiInfo['action'], $apiInfo['params']);
        print_r($result);
    }


    private function _getBankList(){
        $apiInfo = [
            'action' => "Bank/getBankList",
            'params' => [

            ]
        ];

        return $apiInfo;
    }


    private function _captcha(){
        $apiInfo = [
            'action' => "General/captcha2",
            'params' => [

            ]
        ];

        return $apiInfo;
    }


    private function _editPayAccount(){
        $apiInfo = [
            'action' => "PayAccount/editPayAccount",
            'params' => [
                'id' => 6,
                'bankId' => 1,
                'bankAddr' => "fdfdfdfdfd",
                'accountName' => '张催化',
                'accountNumber' => '666666666',
                'limitAmount' => 900,
                'sort' => 0,
                'remark' => '11111',
                'status' => 1,
            ]
        ];

        return $apiInfo;
    }


    private function _getPayAccountList(){

        $apiInfo = [
            'action' => "PayAccount/getPayAccountList",
            'params' => [
            ]
        ];

        return $apiInfo;

    }


    private function _getPayAccountInfo(){

        $apiInfo = [
            'action' => "PayAccount/getPayAccountInfo",
            'params' => [
                'id' => 1
            ]
        ];

        return $apiInfo;

    }


    private function _getPayBankRelationConfigList(){

        $apiInfo = [
            'action' => "PayBankRelation/getConfigList",
            'params' => [
                'channelId' => 18
            ]
        ];

        return $apiInfo;
    }

    private function _editPayBankRelationConfigList(){

        $configData = [
            [
                'channelId' =>   18,
                'bankId' => 8,
                'bankCode' => 'ddff'
            ],
        ];

        $apiInfo = [
            'action' => "PayBankRelation/editConfig",
            'params' => [
                'configData' => urlencode(json_encode($configData)),
            ]
        ];

        return $apiInfo;
    }


    private function _getPayPlatformList(){
        $params = [
            'status' => 0,
            'userLevelId' => 1,
            'page' => 1,
            'num' => 20
        ];

        $apiInfo = [
            'action' => "PayPlatform/getPayPlatformList",
            'params' => $params
        ];

        return $apiInfo;
    }


    private function _getPayChannelList(){
        $apiInfo = [
            'action' => "PayChannel/getPayChannelList",
            'params' => [

            ]
        ];

        return $apiInfo;
    }



    private function _getPayCategoryList(){
        $apiInfo = [
            'action' => "PayCategory/getPayCategoryList",
            'params' => [
                'id' => 1,
            ]
        ];

        return $apiInfo;
    }



    private function _getPayCategoryInfo(){
        $apiInfo = [
            'action' => "PayCategory/getPayCategoryInfo",
            'params' => [
                'id' => 1,
            ]
        ];

        return $apiInfo;
    }



    private function _editPayCategoryInfo(){
        $apiInfo = [
            'action' => "PayCategory/editPayCategoryInfo",
            'params' => [
                'id' => 1,
                'name' => '网银在线支付',
                'image' => '/uploads/images/20170105/586e406bc5862',
                'introduction' => "xxxxxxxx",
                'sort' => 100,
                'status' => 0
            ]
        ];

        return $apiInfo;
    }


    private function _getPayPlatformInfo(){
        $params = [
            'id' => 25
        ];

        $apiInfo = [
            'action' => "PayPlatform/getPayPlatformInfo",
            'params' => $params
        ];

        return $apiInfo;
    }


    private function _editPayPlatformInfo(){
        $apiInfo = [
            'action' => "PayPlatform/editPayPlatformInfo",
            'params' => [
//                'id' => 25,
                'channelId' => 17,
                'categoryId' => 7,
                'redictDomain' => "http://wytj.9vpay.com/PayBank.aspx",
                'accountNo' => '',
                'accountKey' => 'MDAwMDAwMDAwMISdyauPdYecgaW7pbN7r5mw0JGoh3yZrLC5h659rbRhhIfOnI94e5-Dtap2',
                'terminalId' => '',
                'limitAmount' => 0.00,
                'rsaPubkey' => '',
                'rsaPrikey' => '',
                'sort' => 0,
                'status' => 0,
            ],
        ];
        return $apiInfo;
    }


    private function _editPayChannel(){
        $configs = [
            [
                'id' => 10,
                'categoryId' => 1,
                'apiUrl' => 'https://pay.41.cn'
            ],
            [
                'id' => 10,
                'categoryId' => 2,
                'apiUrl' => 'https://pay.41.cn'
            ],
            [
                'id' => 10,
                'categoryId' => 5,
                'apiUrl' => 'https://pay.41.cn'
            ],
            [
                'id' => 10,
                'categoryId' => 6,
                'apiUrl' => 'https://pay.41.cn'
            ],
        ];

        $params = [
            'id' => 10,
            'name' => '易付宝',
            'className' => 'YiFuBao',
            'status' => 1,
            'configs' => urlencode(json_encode($configs))
        ];
        $apiInfo = [
            'action' => "PayChannel/editPayChannel",
            'params' => $params
        ];

        return $apiInfo;
    }

    private function _memberLogin(){
        $apiInfo = [
            'action' => "Member/memberLogin",
            'params' => [
                'nickname' => 'admin',
                'password' => 'admin',
                'captcha' => '0206'
            ]
        ];

        return $apiInfo;
    }

    private function _activeQuery(){
        $apiInfo = [
            'action' => "UserRechargeRecord/activeQuery",
            'params' => [
                'orderId' => '20170212003205324162658',
            ]
        ];

        return $apiInfo;
    }


    public function checkPid() {

        $i = 0;
        $num = 100;

        while(true) {

            $result = Loader::model('User')->where([
                'user_pid' => [
                    'NEQ',
                    0
                ]
            ])->limit($i, $num)->column('user_pid', 'user_id');

            if(!empty($result)) {
                foreach($result as $userId => $userPid) {
                    $this->pids = [];
                    $this->_getPidByUid($userPid);
                    $pidList = implode(',', $this->pids);
                    Loader::model('User')->where(['user_id' => $userId])->update(['user_all_pid' => $pidList]);
                }
                log::write($i);
                $i +=$num;
            }else {
                break;
            }

        }
        echo 'end';
    }


    private function _getPidByUid($userId) {

        $this->pids[] = $userId;
        $result = Loader::model('User')->where(['user_id' => $userId])->column('user_pid','user_id');

        if($result[$userId] >0) {
            $this->_getPidByUid($result[$userId]);
        }

    }
}