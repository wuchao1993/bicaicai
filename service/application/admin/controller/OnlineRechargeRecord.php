<?php
namespace app\admin\controller;

use think\Loader;
use think\Config;
use think\Log;
use think\Request;

class OnlineRechargeRecord{

    /**
     * 获取在线入款列表
     * @param Request $request
     * @return array
     */
    public function getList(Request $request)
    {
        $params = $request->post();
        $logic = Loader::model('OnlineRechargeRecord', 'logic');
        $data = $logic->getList($params);
        foreach ($data ['list'] as &$info) {
            $info = $this->_packInfo($info);
        }

        return send_response($data, $logic->errorcode);
    }


    private function _packInfo($info)
    {
        return [
            'id' => $info ['urr_id'],
            'ulName' => $info ['ul_name'],
            'no' => $info ['urr_no'],
            'uid' => $info ['user_id'],
            'username' => $info ['user_name'],
            'parentUsername' => $info ['parent_user_name'],
            'payPlatform' => $info ['recharge_platform'],
            'payCategoryName' => $info['pay_category_name'],
            'amount' => $info ['urr_amount'],
            'rechargeDiscount' => $info ['urr_recharge_discount'],
            'createtime' => $info ['urr_createtime'],
            'tradetime' => $info ['urr_trade_time'],
            'remark' => $info ['urr_remark'],
            'operatorName' => $info ['operator_name'],
            'status' => Config::get('status.recharge_status_name') [$info ['urr_status']]
        ];
    }


}