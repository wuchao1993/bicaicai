<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;

class PayCenterMerchant extends PayCenterBase {

    public $errorcode = EC_SUCCESS;
    public $errorMessage = '';

    /**
     * 获取商户列表
     */
    public function getList($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }

        $cacheName = Config::get('cache_option.prefix')['pay_center_merchant_list'];
        $apiUrl = Config::get('pay.get_pay_channel_merchant');
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

    public function add($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }

        $apiUrl = Config::get('pay.create_channel_merchant');
        $result = call_pay_center_api($apiUrl,$params);
        return $this->_checkPayCenterResult($result);
    }

    private function _checkPayCenterResult($result){
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $result['data'];
    }

    /**
     * 获取渠道商户详情
     */
    public function getDetail($params){
        $apiUrl = Config::get('pay.get_channel_merchant_detail');
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
     * 修改渠道商户
     */
    public function edit($params){

        if(!empty($params['tag'])) {
            $params['tag'] = implode(',', $params['tag']);
        }
        
        $apiUrl = Config::get('pay.edit_channel_merchant');
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
     * 删除渠道商户
     */
    public function delete($params){
        $apiUrl = Config::get('pay.delete_channel_merchant');
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
     * 启用禁用渠道商户
     */
    public function changeStatus($params){
        $apiUrl = Config::get('pay.change_channel_merchant_status');
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