<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class PayType extends Model {

    public $errorcode = EC_SUCCESS;

    public function getList($page = 1, $num = 10, $type = 1, $typeName = '') {
        $condition = [];
        if($type == 1){
            $condition['pay_gotopay_status'] = array('eq',PAY_PLATFORM_GOTOPAY_START);
        }else{
            $condition = [];
        }

        if (!empty($typeName) ) {
           $condition['pay_type_name'] = [
               'like',
               '%'.$typeName.'%'
           ];
        }
        
        $list      = Loader::model('PayType')->where($condition)->order('pay_type_createtime desc')->limit($num)->page($page)->select();

        $count = Loader::model('PayType')->where($condition)->count();

        return [
            'list'  => $list,
            'count' => $count
        ];
    }

    public function getInfo($id) {
        $condition['pay_type_id'] = $id;

        $info = Loader::model('PayType')->where($condition)->find()->toArray();

        $payTypeConfigList = Loader::model('PayTypeConfig')->where($condition)->select();

        $configList = [];
        $i          = 0;
        foreach(Config::get('status.pay_category_type_name') as $key => $val) {
            $configList[$i]['category_id']   = $key;
            $configList[$i]['category_name'] = $val;
            $configList[$i]['api_url']       = '';
            foreach($payTypeConfigList as &$val2) {
                if($val2['category_id'] == $key) {
                    $configList[$i]['api_url'] = $val2['api_url'];
                }
            }
            $i++;
        }

        $info['configList'] = array_values($configList);

        return $info;
    }

    public function getConfig($pay_type_id,$type ='')
    {
        $condition = array();
        $condition['pay_type_id'] = $pay_type_id;
        if ($type == '1') {
            $condition['category_id'] = array('neq', PAY_PLATFORM_GOTOPAY);
        } else {
            $condition['category_id'] = PAY_PLATFORM_GOTOPAY;
        }
        $condition['api_url'] = array('neq', '');
        $payTypeConfigListId = Loader::model('PayTypeConfig')->where($condition)->column('category_id');
        $payCategoryTypeName = Config::get('status.pay_category_type_name');
        $configList = array();
        $i = 0;
        foreach($payTypeConfigListId as $val){
            $configList[$i]['category_id']   = $val;
            $configList[$i]['category_name'] = $payCategoryTypeName[$val];
            $i++;
        }
        return $configList;
    }

    public function addInfo($info) {

        $configList = $info['configList'];
        unset($info['configList']);

        $info['pay_config_display'] = json_encode($info['pay_config_display']);

        $result = Loader::model('PayType')->save($info);

        if(!empty($configList)) {
            foreach($configList as $val) {
                $data                = [];
                $data['pay_type_id'] = Loader::model('PayType')->pay_type_id;
                $data['category_id'] = $val['categoryId'];
                $data['api_url']     = $val['apiUrl'];
                Loader::model('PayTypeConfig')->insert($data, true);
            }
        }

        return true;
    }

    public function editInfo($info) {

        $configList = $info['configList'];
        unset($info['configList']);

        $info['pay_config_display'] = json_encode($info['pay_config_display']);

        $result = Loader::model('PayType')->save($info, ['pay_type_id' => $info['pay_type_id']]);

        if(!empty($configList)) {
            foreach($configList as $val) {
                $data                = [];
                $data['pay_type_id'] = $info['pay_type_id'];
                $data['category_id'] = $val['categoryId'];
                $data['api_url']     = $val['apiUrl'];

                Loader::model('PayTypeConfig')->insert($data, true);
            }
        }

        return true;
    }
}