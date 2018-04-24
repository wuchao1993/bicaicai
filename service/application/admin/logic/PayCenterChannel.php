<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;

class PayCenterChannel {

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
     * 获取可用渠道列表
     */
    public function getList($params){
        $apiUrl = Config::get('pay.get_pay_channel_list');
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
     * 渠道可用支付类型列表
     */
    public function getUsablePayTypeList($params){
        $apiUrl = Config::get('pay.channel_usable_pay_type_list');
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