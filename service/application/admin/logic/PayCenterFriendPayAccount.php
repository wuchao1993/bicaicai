<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;

class PayCenterFriendPayAccount {

    public $errorcode = EC_SUCCESS;
    public $errorMessage = '';

    private function _buildResponse($data){
        if(empty($data)){
            return [];
        }
        $list = $data['list'];
        foreach ($list as $key => $info){
            $list[$key]['ulId'] = explode(',', $info['tag']);
            unset($list[$key]['tag']);
        }
        $data['list'] = $list;

        return $data;
    }

    /**
     * 获取充值中心好友付账户列表
     * @param  $params
     * @return array
     */
    public function getPayCenterFriendAccountList($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }else {
            unset($params['tag']);
        }

        $apiUrl = Config::get('pay.get_friend_pay_account_list');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }

        //层级信息
        foreach ($result['data']['list'] as $key => &$info){
            $condition = [];
            $condition ['ul_id'] = ['IN',explode(',', $info['tag'])];
            $levelList = Loader::model ( 'UserLevel' )->field ( 'ul_id as id,ul_name as name' )->where ( $condition )->select ();
            $info ['levelList'] = $levelList;
        }

        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }

    /**
     * 添加好友付账户
     * @param $params
     * @return  $status
     */
    public function addFriendAccount($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }

        $apiUrl = Config::get('pay.add_friend_pay_account');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }

    /**
     * 编辑好友付账户
     * @param $params
     * @return  $status
     */
    public function editFriendAccount($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }

        $apiUrl = Config::get('pay.edit_friend_pay_account');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }

    /**
     * 好友付账户详情
     * @param $params
     * @return  $status
     */
    public function getFriendAccountDetail($params){
        $apiUrl = Config::get('pay.get_friend_pay_account_detail');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }

    /**
     * 删除好友付账户
     * @param $params
     * @return  $status
     */
    public function deleteFriendAccount($params){
        $apiUrl = Config::get('pay.delete_friend_pay_account');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }

    /**
     * 修改好友付账户状态
     * @param $params
     * @return  $status
     */
    public function changeFriendAccountStatus($params){
        $apiUrl = Config::get('pay.change_friend_pay_account_status');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }
    
    /**
     * 获取充值中心好友付账户列表
     * @param  $params
     * @return array
     */
    public function getFriendAccountTypeList($params){
        $apiUrl = Config::get('pay.get_friend_pay_type_list');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }

}
