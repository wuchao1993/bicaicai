<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;

class PayChannel extends Model
{

    public $errorcode = EC_SUCCESS;

    public function getList($page = 1, $num = 10 ,$type = 0)
    {
        $condition = [];
        if($type == 0){
            $condition = [];
        }else{
            $condition['pay_gotopay_status'] = array('eq',PAY_PLATFORM_GOTOPAY_START);
        }
        $limit = (($page <= 0) ? 0 : ($page - 1)) * $num . "," . $num;
        $list = Loader::model('PayType')->where($condition)->limit($limit)->select();
        $count =  Loader::model('PayType')->where($condition)->count();
        return [
            'list' => $list,
            'count' => $count
        ];
    }


    public function getInfo($channelId){
        $condition['pay_type_id'] = $channelId;
        return Loader::model('PayType')->where($condition)->find()->toArray();
    }


    public function editInfo($id, $payChannelInfo, $configs)
    {
        $editStatus = Loader::model('PayType')->save($payChannelInfo, ["pay_type_id" => $id]);
        if ($configs) {
            foreach ($configs as $key => $info) {
                $configInfo = [
                    'pay_type_id' => $id,
                    'category_id' => $info['categoryId'],
                    'api_url' => $info['apiUrl']
                ];
                Loader::model('PayTypeConfig')->saveConfig($configInfo);
            }
        }
    }
    
    public function add($payChannelInfo, $configs)
    {
        $id = Loader::model('PayType')->save($payChannelInfo);
        if ($configs) {
            foreach ($configs as $key => $info) {
                $configInfo = [
                        'pay_type_id' => $id,
                        'category_id' => $info['categoryId'],
                        'api_url' => $info['apiUrl']
                ];
                Loader::model('PayTypeConfig')->saveConfig($configInfo);
            }
        }
    }

}