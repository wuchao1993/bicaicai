<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;

class PayCenterCompanyAccount {

    public $errorcode = EC_SUCCESS;
    public $errorMessage = '';

    /**
     * 获取公司入款账户列表
     * @param  $params
     * @return array
     */
    public function getBankAccountList($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }else {
            unset($params['tag']);
        }

        $apiUrl = Config::get('pay.get_bank_account_list');
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
     * 添加公司入款账户
     * @param $params
     * @return  $status
     */
    public function addBankAccount($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }

        $apiUrl = Config::get('pay.add_bank_account');
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
     * 编辑公司付款账户
     * @param $params
     * @return array|bool
     */
    public function editBankAccount($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }

        $apiUrl = Config::get('pay.edit_bank_account');
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
     * 公司付款账户详情
     * @param $params
     * @return  $status
     */
    public function getBankAccountDetail($params){
        $apiUrl = Config::get('pay.get_bank_account_detail');
        $result =  call_pay_center_api($apiUrl,$params);

        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $result['data'];
    }

    /**
     * 删除公司付款账户
     * @param $params
     * @return  $status
     */
    public function deleteBankAccount($params){
        $apiUrl = Config::get('pay.delete_bank_account');
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
    public function changeBankAccountStatus($params){
        $apiUrl = Config::get('pay.change_bank_account_status');
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
     * 获取银行列表
     * @param  $params
     * @return array
     */
    public function getBankAccountBankList($params){
        $apiUrl = Config::get('pay.get_bank_list');
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