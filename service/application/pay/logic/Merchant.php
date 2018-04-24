<?php
namespace app\pay\logic;

use think\Loader;
use think\Config;

class Merchant
{

    public $errorcode = EC_SUCCESS;
    public $errorMessage = "";

    public function __construct()
    {

    }


    public function getMerchantGroup()
    {
        $userId = USER_ID;
        $userInfo = Loader::model('Common/User', 'logic')->getInfoByUid($userId);
        $userLevelId = $userInfo['ul_id'];
        $apiUrl = Config::get('pay.api_get_pay_type_group_list');
        $params['tag'] = $userLevelId;
        $result = call_pay_center_api($apiUrl, $params);
        if($result == false){
            $this->errorcode = EC_PAY_CENTER_ERROR;
            return false;
        }
        $payConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($userLevelId);
        $data = $result['data'];
        foreach ($data as $key=> &$item) {
            if(empty($item['merchant'])){
                continue;
            }
            foreach ($item['merchant'] as &$merchant) {
                $merchant['description'] = $merchant['desc'];
                unset($merchant['desc']);
                $merchant['minAmount'] = isset($merchant['minAmount'])
                    ? max($merchant['minAmount'], $payConfig['pc_online_recharge_min_amount'])
                    : $payConfig['pc_online_recharge_min_amount'];
                $merchant['maxAmount'] = isset($merchant['maxAmount'])
                    ? min($merchant['maxAmount'], $payConfig['pc_online_recharge_max_amount'])
                    : $payConfig['pc_online_recharge_max_amount'];
            }
        }
        
        return $data;
    }

}