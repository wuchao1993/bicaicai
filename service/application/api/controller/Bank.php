<?php

namespace app\api\controller;

use think\Cache;
use think\Config;
use think\Loader;
class Bank
{

    public function getBankList()
    {
        $cacheName = Config::get('cache_option.prefix')['pay_center'].'bank_list';
        $bankLogic = Loader::model('Bank', 'logic');
        if(Cache::has($cacheName)){
            $responses = Cache::get($cacheName);
            $bankLogic->errorcode = EC_SUCCESS;
        }else{
            $data = $bankLogic->getPayCenterBankList();
            $responses = [];
            if($data){
                foreach ($data as $key => $info) {
                    $responses[$key] = $this->_packBankInfo($info);
                }
                Cache::set($cacheName, $responses, 10 * 60);
            }
        }

        return [
            'errorcode' => $bankLogic->errorcode,
            'message' => Config::get('errorcode')[$bankLogic->errorcode],
            'data' => $responses,
        ];
    }


    private function _packBankInfo($info)
    {
        return [
            'id' => $info['bankId'],
            'name' => $info['bankName'],
            'code' => $info['bankCode'],
            'image' => $info['bankMobileImage'],
            'pcImage' => $info['bankImage']
        ];
    }


    /**
     * 获取公司入款类型列表
     * @return array
     */
    public function getCompanyRechargeTypeList()
    {
        $typeConfig = Config::get('status.company_recharge_type');
        $responses = [];
        foreach ($typeConfig as $key => $type) {
            $responses[] = [
                'id' => $key,
                'name' => $type
            ];
        }

        return [
            'errorcode' => EC_SUCCESS,
            'message' => Config::get('errorcode')[EC_SUCCESS],
            'data' => $responses,
        ];
    }


}