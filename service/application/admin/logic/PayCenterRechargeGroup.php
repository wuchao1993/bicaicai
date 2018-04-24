<?php
namespace app\admin\logic;

use think\Config;

class PayCenterRechargeGroup{

    public $errorcode = EC_SUCCESS;
    public $errorMessage = "";

    public function getList($params){
        $apiUrl = Config::get('pay.get_pay_type_group_list');
        $result = call_pay_center_api($apiUrl,$params);
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $this->errorcode = $result['code'];
        $this->errorMessage = $result['message'];
        return $this->_buildResponse($result['data']);
    }


    /**
     * @param $params
     * @return array|bool
     */
    public function add($params){
        $apiUrl = Config::get('pay.add_pay_type_group');
        $result = call_pay_center_api($apiUrl,$params);
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
    }


    /**
     * 编辑支付类型分组
     * @param $params
     * @return bool
     */
    public function edit($params){
        $apiUrl = Config::get('pay.edit_pay_type_group');
        $result = call_pay_center_api($apiUrl,$params);
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }

        return true;
    }


    /**
     * 删除支付类型分组
     * @param  $params
     * @return bool
     */
    public function delete($params)
    {
        $apiUrl = Config::get('pay.delete_pay_type_group');
        $result = call_pay_center_api($apiUrl, $params);
        if ($result == false) {
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }

        return true;
    }


    /**
     * 修改支付类型分组状态
     * @param $params
     * @return  bool
     */
    public function changeStatus($params){
        $apiUrl = Config::get('pay.change_pay_type_group_status');
        $result = call_pay_center_api($apiUrl, $params);
        if ($result == false) {
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
            $list[$key]['ulId'] = $info['tag'];
            unset($list[$key]['tag']);
        }
        $data['list'] = $list;

        return $data;
    }
}