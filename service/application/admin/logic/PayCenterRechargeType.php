<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use think\Cache;
use curl\Curlrequest;

class PayCenterRechargeType {

    public $errorcode = EC_SUCCESS;
    public $errorMessage = "";

    /**
     * 获取支付类型列表
     * @param  $params
     * @return array|bool
     */
    public function getList($params) {

        $apiUrl = Config::get('pay.get_pay_type_list');
        $result = call_pay_center_api($apiUrl, $params);

        if ($result == false) {
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];

        return $this->_buildResponse($result['data']);
    }

    /**
     * 修改支付类型分组
     * @param $params
     * @return  bool
     */
    public function updateGroup($params){
        $apiUrl = Config::get('pay.update_pay_type_group');
        $result = call_pay_center_api($apiUrl,$params);
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }

        return true;
    }


    private function _buildResponse($data){
        if(empty($data)){
            return [];
        }
        $list = $data['list'];
        foreach ($list as $key => $info){
            $list[$key]['groupId'] = explode(',', $info['groupId']);
            $list[$key]['ulId'] = $info['tag'];
            unset($list[$key]['tag']);
        }
        $data['list'] = $list;

        return $data;
    }

	
}