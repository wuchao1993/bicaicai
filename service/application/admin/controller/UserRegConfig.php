<?php

/**
 * 用户注册优惠控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class UserRegConfig {

    /**
     * 获取用户注册优惠信息
     *
     * @param Request $request
     * @return array
     */
    public function getInfo(Request $request) {
        $userRegConfigLogic = Loader::model('UserRegConfig', 'logic');
        $regInfo            = $userRegConfigLogic->getInfo();
        $regInfo            = $this->_packInfo($regInfo);

        return [
            'errorcode' => $userRegConfigLogic->errorcode,
            'message'   => Config::get('errorcode') [$userRegConfigLogic->errorcode],
            'data'      => $regInfo
        ];
    }

    /**
     * 获取金流类型
     * @param Request $request
     * @return array
     */
    public function getDiscountTypeList(Request $request) {
        $userRegConfigLogic = Loader::model('UserRegConfig', 'logic');

        $data = array();
        foreach(Config::get('status.reg_discount_type_name') as $key => $val) {
            $data[$key - 1] = array(
                'id'    => $key,
                'value' => $val
            );
        }

        return [
            'errorcode' => $userRegConfigLogic->errorcode,
            'message'   => Config::get('errorcode')[$userRegConfigLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 编辑用户注册优惠
     * @param Request $request
     * @return array
     */
    public function editRegConfig(Request $request) {
        $params['urc_id']                   = $request->param('id','');
        $params['urc_is_discount']          = $request->param('isDiscount','');
        $params['urc_discount_amount']      = $request->param('discountAmount',0);
        $params['urc_type']                 = $request->param('type/a');
        $params['urc_check_amount']         = $request->param('checkAmount',0);
        $params['urc_remark']               = $request->param('remark','');
        $params['urc_ip_day_limit']         = $request->param('ipDayLimit',0);
        $params['urc_isonly_general_agent'] = $request->param('isOnlyGeneralAgent');
        $params['urc_reg_ip_isrepeat']      = $request->param('regIpIsrepeat');
        $params['urc_regip_bindip_issame']  = $request->param('regipBindipIssame');

        $userRegConfigLogic = Loader::model('UserRegConfig', 'logic');
        $result             = $userRegConfigLogic->editRegConfig($params);

        return [
            'errorcode' => $userRegConfigLogic->errorcode,
            'message'   => Config::get('errorcode')[$userRegConfigLogic->errorcode],
            'data'      => $result,
        ];
    }

    private function _packInfo($info) {
        return [
            'id'             => $info ['urc_id'],
            'isDiscount'     => $info ['urc_is_discount'],
            'discountAmount' => $info ['urc_discount_amount'],
            'checkAmount'    => $info ['urc_check_amount'],
            'remark'         => $info ['urc_remark'],
            'type'           => explode(',', $info ['urc_type']),
            'ipDayLimit'     => $info ['urc_ip_day_limit'],
            'isOnlyGeneralAgent'     => $info ['urc_isonly_general_agent'],
            'regIpIsrepeat'          => $info ['urc_reg_ip_isrepeat'],
            'regipBindipIssame'      => $info ['urc_regip_bindip_issame']
        ];
    }

}
