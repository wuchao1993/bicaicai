<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
use curl\Curlrequest;
class Bank extends Model
{
    public $errorcode = EC_SUCCESS;

    public function getList($page=1, $num=10, $params)
    {
        $limit = (($page<=0) ? 0 : ($page-1))*$num . "," .$num;
        $condition = [];

        if(isset($params['bank_name']) && $params['bank_name'] != '') {
            $condition['bank_name'] = ['LIKE','%'.$params['bank_name'].'%'];
        }
        
        $fields = "bank_id,bank_image_pc,bank_image_mobile,bank_name,bank_code,bank_status,bank_createtime";
        $list = Loader::model('Bank')->where($condition)->limit($limit)->column($fields, "bank_id");
        $count = Loader::model('Bank')->where($condition)->count();
        return [
            'list' => array_values($list),
            'count' => $count
        ];
    }

    public function getPayCenterBankList($params){
        $merchantInfo = Loader::model('PayCenterMerchantInfo')->find();
        $url = Config::get('pay.get_bank_list_url');
        $signKey = $merchantInfo['sign_key'];
        $params['merchantId'] = $merchantInfo['merchant_id'];
        $params['pageSize'] = $params['num'];
        $params['page'] = $params['page'] ? $params['page'] : 1;
        $params['nonce'] = random_string(32);
        $sign = build_request_sign($params, $signKey);
        $params['sign'] = $sign;
        $result = json_decode(Curlrequest::post($url, $params), true);
        return $result;            
    }


    public function getInfo($id)
    {
        $condition = ['bank_id' => $id];
        $info = Loader::model('Bank')->where($condition)->find()->toArray();

        return $info;
    }


    public function editInfo($id, $info)
    {
        //获取信息
        $bankInfo = Loader::model('Bank')->where(['bank_name' => $info['bank_name']])->find();
        if (!empty($bankInfo) && $bankInfo['bank_id'] != $id) {
            $this->errorcode = EC_AD_BANK_EXISTING;
            return false;
        }
        
        $result = Loader::model('Bank')->save($info, ['bank_id' => $id]);

        return $result;
    }


    public function add($info)
    {
        //获取信息
        $bankInfo = Loader::model('Bank')->where(['bank_name' => $info['bank_name']])->find();
        if (!empty($bankInfo)) {
            $this->errorcode = EC_AD_BANK_EXISTING;
            return false;
        }
        
        $result = Loader::model('Bank')->insertGetId($info);
        if ($result) {
            $addInfo = [
                    'id' => $result
            ];
            return $addInfo;
        }
        
        return false;
    }


}