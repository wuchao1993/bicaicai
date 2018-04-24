<?php

namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayCenterFriendPayAccount {

    public function getPayCenterFriendAccountList(Request $request) {
        $params['page'] = $request->param('page');
        $params['size'] = $request->param('num');
        $params['tag']  = $request->param('ulId/a');
        $payCenter      = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data           = $payCenter->getPayCenterFriendAccountList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 添加好友付账户
     */
    public function addFriendAccount(Request $request) {
        $params['account']   = $request->param('account');
        $params['realName']  = $request->param('realName'); //好友支付账号真实姓名
        $params['typeCode']  = $request->param('typeCode');
        $params['qrCodeUrl'] = $request->param('qrCodeUrl');
        $params['tag']       = $request->param('ulId/a'); //好友层级
        $params['status']    = $request->param('status'); //好友类型代码,wechat,alipay,qq
        $payCenter           = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data                = $payCenter->addFriendAccount($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 编辑好友付账户
     */
    public function editFriendAccount(Request $request) {
        $params['account']   = $request->param('account');
        $params['realName']  = $request->param('realName'); //好友支付账号真实姓名
        $params['typeCode']  = $request->param('typeCode');
        $params['qrCodeUrl'] = $request->param('qrCodeUrl');
        $params['tag']       = $request->param('ulId/a'); //好友层级
        $params['status']    = $request->param('status'); //好友类型代码,wechat,alipay,qq
        $params['id']        = $request->param('id');
        $payCenter           = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data                = $payCenter->editFriendAccount($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 好友付账户详情
     */
    public function getFriendAccountDetail(Request $request) {
        $params['id'] = $request->param('id');
        $payCenter    = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data         = $payCenter->getFriendAccountDetail($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }


    /**
     * 删除好友付账户
     */
    public function deleteFriendAccount(Request $request) {
        $params['id'] = $request->param('id');
        $payCenter    = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data         = $payCenter->deleteFriendAccount($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 修改好友付账户状态
     */
    public function changeFriendAccountStatus(Request $request) {
        $params['id']     = $request->param('id');
        $params['status'] = $request->param('status');
        $payCenter        = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data             = $payCenter->changeFriendAccountStatus($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 好友付类型列表
     */
    public function getFriendAccountTypeList(Request $request) {
        $params    = [];
        $payCenter = Loader::model('PayCenterFriendPayAccount', 'logic');
        $data      = $payCenter->getFriendAccountTypeList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

}