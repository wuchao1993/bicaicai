<?php
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayBankRelation
{

    public function getConfigList(Request $request)
    {
        $channelId = $request->param('channelId');
        $type      = $request->param('type');
        $payBankRelationLogic = Loader::model('PayBankRelation', 'logic');
        $data = $payBankRelationLogic->getConfigList($channelId,$type);
        $responseList = [];
        foreach ($data as $key => $info){
            $responseList[$key] = $this->_packConfigInfo($info);
        }

        return [
            'errorcode' => $payBankRelationLogic->errorcode,
            'message' => Config::get('errorcode')[$payBankRelationLogic->errorcode],
            'data' => $responseList,
        ];
    }


    private function _packConfigInfo($info)
    {
        return [
            'channelId' => $info['pay_type_id'],
            'bankId'    => $info['bank_id'],
            'bankCode'  => $info['bank_code'],
            'bankType'  => $info['pay_type'],
        ];
    }



    public function editConfig(Request $request){
        $configData = $request->param('configData/a');
        $payBankRelationLogic = Loader::model('PayBankRelation', 'logic');
        if(!empty($configData)) {
            foreach ($configData as $info){

                $confitInfo['pay_type_id'] = !empty($info['channelId']) ? $info['channelId'] : 1;
                $confitInfo['bank_id']     = !empty($info['bankId']) ? $info['bankId'] : 0;
                $confitInfo['bank_code']   = !empty($info['bankCode']) ? $info['bankCode'] : '';
                $confitInfo['pay_type']    = !empty($info['type']) ? $info['type'] : 0;
                $payBankRelationLogic->editConfig($confitInfo);
            }
        }
        
        return [
                'errorcode' => $payBankRelationLogic->errorcode,
                'message' => Config::get('errorcode')[$payBankRelationLogic->errorcode],
        ];
    }


}