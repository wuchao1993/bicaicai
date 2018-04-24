<?php
/**
 * 用户账户
 * @createTime 2017/4/25 11:26
 */

namespace app\api\controller;

use app\common\model\RechargeTypeGroup;
use think\Config;
use think\Request;
use think\Loader;
use think\Hook;

class Account {

    /**
     * 获取用户信息
     * @return array
     */
    public function getUserInfo() {
        Hook::listen('auth_check');
        $userLogic = Loader::model('User', 'logic');
        $info = $userLogic->getInfoByUid(USER_ID, false, true);
        return return_result($userLogic->errorcode, output_format($info));
    }

    /**
     * 账户明细
     * @param Request $request
     * @return array
     */
    public function detailRecords(Request $request) {
        Hook::listen('auth_check');
        $type = $request->param('typeId');
        $page = $request->param('page');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getDetails($type, $page);
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 返回账户明细的交易类型
     * @return array
     */
    public function detailTypes() {
        Hook::listen('auth_check');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getDetailTypes();
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 充值记录
     * @param Request $request
     * @return array
     */
    public function rechargeRecords(Request $request) {
        Hook::listen('auth_check');
        $type = $request->param('typeId');
        $page = $request->param('page');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getRechargeRecords($type, $page);
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * pc端充值记录
     * @param Request $request
     * @return array
     */
    public function pcRechargeRecords(Request $request) {
        Hook::listen('auth_check');
        $params['type']       = $request->param('typeId');
        $params['page']       = $request->param('page');
        $params['order_no']   = $request->param('orderNo');
        $params['start_time'] = $request->param('startTime');
        $params['end_time']   = $request->param('endTime');
        $params['status']     = $request->param('status');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getPcRechargeRecords($params);
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 返回充值类型
     * @return array
     */
    public function rechargeTypes() {
        Hook::listen('auth_check');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getRechargeTypes();
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 提现记录
     * @param Request $request
     * @return array
     */
    public function withdrawRecords(Request $request) {
        Hook::listen('auth_check');
        $type = $request->param('typeId');
        $page = $request->param('page');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getWithdrawRecords($type, $page);
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 提现记录
     * @param Request $request
     * @return array
     */
    public function pcWithdrawRecords(Request $request) {
        Hook::listen('auth_check');
        $params['type']       = $request->param('typeId');
        $params['page']       = $request->param('page');
        $params['order_no']   = $request->param('orderNo');
        $params['start_time'] = $request->param('startTime');
        $params['end_time']   = $request->param('endTime');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getPcWithdrawRecords($params);
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 返回提现类型
     * @return array
     */
    public function withdrawTypes() {
        Hook::listen('auth_check');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->getWithdrawTypes();
        return return_result($accountLogic->errorcode, output_format($data));
    }

    /**
     * 返回公告分类
     * @return array
     */
    public function noticeTypes() {
        $data = Loader::model('Notice', 'logic')->getNoticeTypes();
        return return_result(EC_SUCCESS, output_format($data));
    }

    /**
     * 根据公告类型获取公告列表
     * @param Request $request
     * @return array
     */
    public function noticeList(Request $request) {
        $typeId = $request->param('typeId');
        $page = $request->param('page');
        $noticeLogic = Loader::model('Notice', 'logic');
        $data = $noticeLogic->getListByTypeId($typeId, $page);
        return return_result($noticeLogic->errorcode, output_format($data));
    }

    /**
     * 修改密码
     * @param Request $request
     * @return array
     */
    public function changePassword(Request $request) {
        Hook::listen('auth_check');
        $oldPassword = $request->post('oldPassword');
        $newPassword = $request->post('newPassword');
        $newPasswordConfirm = $request->post('newPasswordConfirm');
        $accountLogic = Loader::model('Account', 'logic');
        $data = $accountLogic->changePassword($oldPassword, $newPassword, $newPasswordConfirm);
        return [
            'errorcode' => $accountLogic->errorcode,
            'message'   => $accountLogic->message ?: Config::get('errorcode')[$accountLogic->errorcode],
            'data'      => output_format($data)
        ];
    }
    
    /**
     * @param Request $request
     * @return array
     */
    public function changeFundsPassword(Request $request){
        Hook::listen('auth_check');
        $oldFundsPassword = $request->post('oldFundsPassword');
        $newFundsPassword = $request->post('newFundsPassword');
        $accountLogic = Loader::model('Account', 'logic');
        $accountLogic->changeFundsPassword($oldFundsPassword, $newFundsPassword);
        return return_result($accountLogic->errorcode);
    }

    /**
     * 获取用户银行卡列表
     * @return mixed
     */
    public function getBanks() {
        Hook::listen('auth_check');
        $userLogic = Loader::model('User', 'logic');
        $bankList = $userLogic->getBanks();
        return return_result($userLogic->errorcode, $bankList);
    }

    /**
     * 获取公司入款帐号列表
     * @return array
     */
    public function getCompanyBanks() {
        Hook::listen('auth_check');
        $payAccountLogic = Loader::model('payAccount', 'logic');
        $bankList = $payAccountLogic->getCompanyBankList();
        return return_result($payAccountLogic->errorcode, $bankList);
    }

    /**
     * 下注限额设置列表
     * @param Request $request
     * @return array
     */
    public function betLimitSetting(Request $request) {
        Hook::listen('auth_check');
        $sportId = $request->param('sportId');
        $logic = Loader::model('PlayTypes', 'logic');
        $data = $logic->getBetLimitSetting($sportId);
        return return_result($logic->errorcode, $data);
    }

    /**
     * 获取用户正在下注时的限额设置列表
     * @param Request $request
     * @return array
     */
    public function bettingLimitSetting(Request $request) {
        Hook::listen('auth_check');
        $sportId = $request->param('sportId');
        $eventType = $request->param('eventType');
        $logic = Loader::model('PlayTypes', 'logic');
        $data = $logic->getBettingLimitSetting($sportId, $eventType);
        return return_result($logic->errorcode, output_format($data));
    }
}