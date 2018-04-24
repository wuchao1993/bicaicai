<?php

/**
 * 用户控制器
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class User {

    /**
     * 获取用户列表
     *
     * @param Request $request
     * @return array
     */
    public function getUserList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 100);

        if($request->param('pid') != '') {
            $params ['user_pid'] = $request->param('pid');
        }
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('userIsAgent') != '') {
            $params ['user_is_agent'] = $request->param('userIsAgent');
        }
        if($request->param('userBankNo') != '') {
            $params ['user_bank_no'] = $request->param('userBankNo');
        }
        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('realname') != '') {
            $params ['user_realname'] = $request->param('realname');
        }
        if($request->param('ulId') != '') {
            $params ['ul_id'] = $request->param('ulId');
        }
        if($request->param('sortMode') != '') {
            $params ['sortMode'] = $request->param('sortMode');
        }
        if($request->param('isPreciseQuery') != '') {
            $params ['is_precise_query'] = $request->param('isPreciseQuery');
        }
        if($request->param('userMobile') != '') {
            $params ['user_mobile'] = $request->param('userMobile');
        }
        if($request->param('userRegTerminal') != '') {
            $params ['reg_terminal'] = $request->param('userRegTerminal');
        }
        if($request->param('channelName') != '') {
            $params ['channel_name'] = $request->param('channelName');
        } 
        if($request->param('qq') != '') {
            $params ['user_qq'] = $request->param('qq');
        }    
        if($request->param('email') != '') {
            $params ['user_email'] = $request->param('email');
        }
        if($request->param('onlineUsers')) {
            $params ['online_users'] = true;
        }

        $userLogic = Loader::model('User', 'logic');
        $userList  = $userLogic->getList($params);

        foreach($userList ['list'] as &$info) {
            $info = $this->_packUserList($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userList)
        ];
    }

    /**
     * 导出excel表
     *
     * @param Request $request
     * @return array
     */
    public function exportExcel(Request $request){
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 100);

        if($request->param('pid') != '') {
            $params ['user_pid'] = $request->param('pid');
        }
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('userIsAgent') != '') {
            $params ['user_is_agent'] = $request->param('userIsAgent');
        }
        if($request->param('userBankNo') != '') {
            $params ['user_bank_no'] = $request->param('userBankNo');
        }
        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('realname') != '') {
            $params ['user_realname'] = $request->param('realname');
        }
        if($request->param('ulId') != '') {
            $params ['ul_id'] = $request->param('ulId');
        }
        if($request->param('sortMode') != '') {
            $params ['sortMode'] = $request->param('sortMode');
        }
        if($request->param('isPreciseQuery') != '') {
            $params ['is_precise_query'] = $request->param('isPreciseQuery');
        }
        if($request->param('userMobile') != '') {
            $params ['user_mobile'] = $request->param('userMobile');
        }
        if($request->param('userRegTerminal') != '') {
            $params ['reg_terminal'] = $request->param('userRegTerminal');
        }
        if($request->param('channelName') != '') {
            $params ['channel_name'] = $request->param('channelName');
        }
        //导出excel表
        $params ['export_excel'] = $request->param('exportExcel');
        $userLogic = Loader::model('User', 'logic');
        $reportUrl  = $userLogic->getList($params);
        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => ['reportUrl' => $reportUrl]
        ];
    }

    /**
     * 获取用户信息
     *
     * @param Request $request
     * @return array
     */
    public function getUserInfo(Request $request) {
        $uid = $request->param('uid');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->getInfo($uid);
        $userInfo  = $this->_packUserInfo($userInfo);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userInfo)
        ];
    }

    /**
     * 获取用户额外信息
     *
     * @param Request $request
     * @return array
     */
    public function getUserExtendInfo(Request $request) {
        $username = $request->param('username');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->getExtendInfo($username);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userInfo)
        ];
    }

    /**
     * 获取用户银行信息
     *
     * @param Request $request
     * @return array
     */
    public function getUserBankList(Request $request) {
        $uid = $request->param('uid');

        $userLogic    = Loader::model('User', 'logic');
        $userBankList = $userLogic->getUserBankList($uid);

        foreach($userBankList as &$info) {
            $info = $this->_packUserBankList($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userBankList)
        ];
    }

    /**
     * 获取用户额外信息
     *
     * @param Request $request
     * @return array
     */
    public function getUserBankInfo(Request $request) {
        $ubId = $request->param('ubId');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->getUserBankInfo($ubId);
        $userInfo  = $this->_packUserBankInfo($userInfo);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userInfo)
        ];
    }

    /**
     * 获取用户返点信息
     *
     * @param Request $request
     * @return array
     */
    public function getUserRebateInfo(Request $request) {
        $uid = $request->param('uid');

        $userLogic      = Loader::model('User', 'logic');
        $userRebateInfo = $userLogic->getUserRebateInfo($uid);

        foreach($userRebateInfo as &$info) {
            $info = $this->_packUserRebateInfo($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userRebateInfo)
        ];
    }

    /**
     * 获取用户统计资料
     *
     * @param Request $request
     * @return array
     */
    public function getUserStatistics(Request $request) {
        $uid = $request->param('uid');

        $userLogic      = Loader::model('User', 'logic');
        $userStatistics = $userLogic->getUserStatistics($uid);
        $userStatistics = $this->_packUserStatistics($userStatistics);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userStatistics)
        ];
    }

    /**
     * 获取用户金流记录
     *
     * @param Request $request
     * @return array
     */
    public function getUserAccountRecord(Request $request) {
        $params ['user_id'] = $request->param('uid');
        $params ['page']    = $request->param('page', 1);
        $params ['num']     = $request->param('num', 10);

        if ($request->param ( 'startDate' ) != '') {
            $params ['start_date'] = date ( 'Y-m-d 00:00:00', strtotime ( $request->param ( 'startDate' ) ) );
        }else{
            $params ['start_date'] = date ( 'Y-m-d 00:00:00', strtotime ("-30 day") );
        }
        if ($request->param ( 'endDate' ) != '') {
            $params ['end_date'] = date ( 'Y-m-d 23:59:59', strtotime ( $request->param ( 'endDate' ) ) );
        }else{
            $params ['end_date'] = current_datetime() ;
        }

        $params ['date_type'] = $request->param ( 'dateType', 1 );

        $params ['uar_action_type'] = $request->param ( 'actionType', " " );

        $params ['uar_transaction_type'] = $request->param ( 'transactionType/a', [] );
        
        $userAccountRecordLogic = Loader::model ( 'UserAccountRecord', 'logic' );
        $accountList = $userAccountRecordLogic->getAccountListColdData ( $params );

        foreach ( $accountList ['list'] as &$info ) {
            $info = $this->_packAccountInfo ( $info );
        }
        
        return [
            'errorcode' => $userAccountRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userAccountRecordLogic->errorcode],
            'data'      => output_format($accountList)
        ];
    }

    /**
     * 获取用户注单列表
     *
     * @param Request $request
     * @return array
     */
    public function getUserOrderList(Request $request) {
        $params ['user_id'] = $request->param('uid');
        $params ['page']    = $request->param('page', 1);
        $params ['num']     = $request->param('num', 10);

        if($request->param('lotteryId') != '') {
            $params ['lottery_id'] = $request->param('lotteryId');
        }
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('issueNo') != '') {
            $params ['issue_no'] = $request->param('issueNo');
        }
        if($request->param('status') != '') {
            $params ['order_status'] = $request->param('status');
        }

        $userLogic     = Loader::model('User', 'logic');
        $userOrderList = $userLogic->getUserOrderList($params);

        foreach($userOrderList ['list'] as &$info) {
            $info = $this->_packUserOrderInfo($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userOrderList)
        ];
    }

    /**
     * 新增用户
     *
     * @param Request $request
     * @return array
     */
    public function addUser(Request $request) {
        $params ['user_name']     = trim($request->param('username'));
        $params ['user_realname'] = $request->param('realname');
        $params ['user_password'] = $request->param('password');
        $params ['user_mobile']   = $request->param('mobile');
        $params ['user_email']    = $request->param('email');
        $params ['user_is_agent'] = $request->param('userIsAgent');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->add($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userInfo)
        ];
    }

    /**
     * 编辑用户
     *
     * @param Request $request
     * @return array
     */
    public function editUser(Request $request) {
        $params ['user_id']             = $request->param('uid');
        $params ['user_nickname']       = $request->param('nickname');
        $params ['user_password']       = $request->param('loginPassword');
        $params ['user_mobile']         = trim($request->param('mobile'));
        $params ['user_email']          = $request->param('email');
        $params ['user_qq']             = $request->param('qq');
        $params ['user_remark']         = $request->param('remark');
        $params ['user_status']         = $request->param('status');
        $params ['user_is_agent']       = $request->param('userIsAgent');

        if($request->param('userAgentCheckStatus') != '') {
            $params ['user_agent_check_status'] = $request->param('userAgentCheckStatus');
        }

        if($request->param('userContactInfo') != '') {
            $params ['user_contact_info'] = $request->param('userContactInfo');
        }

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->edit($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 新增用户银行资料
     *
     * @param Request $request
     * @return array
     */
    public function addUserBank(Request $request) {
        $params ['user_id']   = $request->param('uid');
        $params ['bank_list'] = $request->param('bankList/a');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->addBank($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑用户银行资料
     *
     * @param Request $request
     * @return array
     */
    public function editUserBank(Request $request) {
        $params ['user_id']   = $request->param('uid');
        $params ['bank_list'] = $request->param('bankList/a');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editBank($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 删除用户银行资料
     *
     * @param Request $request
     * @return array
     */
    public function delUserBank(Request $request) {
        $params ['user_id'] = $request->param('uid');
        $params ['ub_id'] = $request->param('ubId');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->delBank($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑用户返点信息
     *
     * @param Request $request
     * @return array
     */
    public function editUserRebate(Request $request) {
        $params ['user_id']     = $request->param('uid');
        $params ['rebate_list'] = $request->param('rebateList/a');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editRebate($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 删除用户
     *
     * @param Request $request
     * @return array
     */
    public function delUser(Request $request) {
        $params ['user_id'] = $request->param('uid');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->del($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 修改用户状态
     *
     * @param Request $request
     * @return array
     */
    public function changeUserStatus(Request $request) {
        $params ['user_id']     = $request->param('uid');
        $params ['user_status'] = $request->param('status');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->changeStatus($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 获取用户登陆日志列表
     *
     * @param Request $request
     * @return array
     */
    public function getLoginLogList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('loginIp') != '') {
            $params ['ull_login_ip'] = $request->param('loginIp');
        }
        if($request->param('isPreciseQuery') != '') {
            $params ['isPreciseQuery'] = $request->param('isPreciseQuery');
        }

        $userLogic    = Loader::model('User', 'logic');
        $loginLogList = $userLogic->getLoginLogList($params);

        foreach($loginLogList ['list'] as &$info) {
            $info = $this->_packUserLoginLogInfo($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($loginLogList)
        ];
    }

    /**
     * 获取同IP用户列表
     *
     * @param Request $request
     * @return array
     */
    public function getSameIpList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('loginIp') != '') {
            $params ['ull_login_ip'] = $request->param('loginIp');
        }

        $userLogic    = Loader::model('User', 'logic');
        $loginLogList = $userLogic->getSameIpList($params);

        foreach($loginLogList ['list'] as &$info) {
            $info = $this->_packUserLoginLogInfo($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($loginLogList)
        ];
    }

    /**
     * 获取代理待审核列表
     *
     * @param Request $request
     * @return array
     */
    public function getAgentPendingList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('userAgentCheckStatus') != '') {
            $params ['user_agent_check_status'] = $request->param('userAgentCheckStatus');
        }

        $userLogic        = Loader::model('User', 'logic');
        $agentPendingList = $userLogic->getAgentPendingList($params);

        foreach($agentPendingList['list'] as &$info) {
            $info = $this->_packUserList($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($agentPendingList)
        ];
    }

    /**
     * 审核代理
     *
     * @param Request $request
     * @return array
     */
    public function changeAgentStatus(Request $request) {
        $params ['user_id']                 = $request->param('uid');
        $params ['user_agent_check_status'] = $request->param('userAgentCheckStatus');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->changeAgentStatus($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 获取会员层级列表
     *
     * @param Request $request
     * @return array
     */
    public function getUserLevelList(Request $request) {
        $params = array();
        if($request->param('ulName') != '') {
            $params ['ul_name'] = $request->param('ulName');
        }

        $userLogic     = Loader::model('User', 'logic');
        $userLevelList = $userLogic->getUserLevelList($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userLevelList)
        ];
    }

    //增加用户层级的同时，设置层级配置
    public function addUserLevelAndEditPayConfig(Request $request)
    {
        $addRes = $this->addUserLevel($request);
        if ( $addRes['errorcode'] != EC_AD_SUCCESS ) {
            return $addRes;
        }
        //用户层级id
        $ulId = $addRes['data']['ulId'];

        $editRes = $this->editLevelPayConfig($request, $ulId);
        if ( $editRes['errorcode'] != EC_AD_SUCCESS ) {
            return $editRes;
        }

        return $addRes;

    }

    /**
     * 新增用户层级
     *
     * @param Request $request
     * @return array
     */
    public function addUserLevel(Request $request) {
        $params ['ul_name']                     = $request->param('ulName');
        $params ['ul_description']              = $request->param('ulDescription');
        $params ['ul_user_create_start_time']   = $request->param('ulUserCreateStartTime') ? $request->param('ulUserCreateStartTime') : date('Y-m-d');
        $params ['ul_user_create_end_time']     = $request->param('ulUserCreateEndTime') ? $request->param('ulUserCreateEndTime') : date('Y-m-d');
        $params ['ul_user_recharge_start_time'] = $request->param('ulUserRechargeStartTime') ? $request->param('ulUserRechargeStartTime') : date('Y-m-d');
        $params ['ul_user_recharge_end_time']   = $request->param('ulUserRechargeEndTime') ? $request->param('ulUserRechargeEndTime') : date('Y-m-d');
        $params ['ul_recharge_count']           = $request->param('ulRechargeCount', 0);
        $params ['ul_recharge_amount']          = $request->param('ulRechargeAmount', 0);
        $params ['ul_withdraw_count']           = $request->param('ulWithdrawCount', 0);
        $params ['ul_withdraw_amount']          = $request->param('ulWithdrawAmount', 0);
        $params ['ul_status']                   = $request->param('ulStatus', 1);
        $params ['ul_default']                  = $request->param('ulDefault', 0);

        $params ['pc_online_recharge_max_amount'] = $request->param('onlineRechargeMaxAmount');
        $params ['pc_online_recharge_min_amount'] = $request->param('onlineRechargeMinAmount', 1);
        $params ['pc_relax_amount']               = $request->param('relaxAmount', 1);

        $userLogic     = Loader::model('User', 'logic');
        $userLevelInfo = $userLogic->addUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userLevelInfo)
        ];
    }

    /**
     * 编辑用户层级
     *
     * @param Request $request
     * @return array
     */
    public function editUserLevel(Request $request) {
        $params ['ul_id']                       = $request->param('ulId');
        $params ['ul_name']                     = $request->param('ulName');
        $params ['ul_description']              = $request->param('ulDescription');
        $params ['ul_user_create_start_time']   = $request->param('ulUserCreateStartTime') ? $request->param('ulUserCreateStartTime') : date('Y-m-d');
        $params ['ul_user_create_end_time']     = $request->param('ulUserCreateEndTime') ? $request->param('ulUserCreateEndTime') : date('Y-m-d');
        $params ['ul_user_recharge_start_time'] = $request->param('ulUserRechargeStartTime') ? $request->param('ulUserRechargeStartTime') : date('Y-m-d');
        $params ['ul_user_recharge_end_time']   = $request->param('ulUserRechargeEndTime') ? $request->param('ulUserRechargeEndTime') : date('Y-m-d');
        $params ['ul_recharge_count']           = $request->param('ulRechargeCount', 0);
        $params ['ul_recharge_amount']          = $request->param('ulRechargeAmount', 0);
        $params ['ul_withdraw_count']           = $request->param('ulWithdrawCount', 0);
        $params ['ul_withdraw_amount']          = $request->param('ulWithdrawAmount', 0);
        $params ['ul_status']                   = $request->param('ulStatus', 1);
        $params ['ul_default']                  = $request->param('ulDefault', 0);

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 设置默认用户层级
     *
     * @param Request $request
     * @return array
     */
    public function setDefaultUserLevel(Request $request) {
        $params ['ul_id']      = $request->param('ulId');
        $params ['ul_default'] = $request->param('ulDefault');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->setDefaultUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 分层
     *
     * @param Request $request
     * @return array
     */
    public function updateUserLevel(Request $request) {
        $params ['ul_id']         = $request->param('ulId/a');
        $params ['curr_level_id'] = $request->param('currLevelId');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->newUpdateUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 回归
     *
     * @param Request $request
     * @return array
     */
    public function regressUserLevel(Request $request) {
        $params ['ul_id'] = $request->param('ulId');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->regressUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 删除层级
     *
     * @param Request $request
     * @return array
     */
    public function delUserLevel(Request $request) {
        $params ['ul_id'] = $request->param('ulId');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->delUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 获取会员层级支付配置列表
     *
     * @return array
     */
    public function getLevelPayConfigList() {

        $userLogic     = Loader::model('User', 'logic');
        $userLevelList = $userLogic->getLevelPayConfigList();
        foreach($userLevelList as &$info) {
            $info = $this->_packLevelConfigInfo($info);
        }

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userLevelList)
        ];
    }

    /**
     * 获取会员层级支付配置
     *
     * @param Request $request
     * @return array
     */
    public function getLevelPayConfig(Request $request) {
        $params = array();
        if($request->param('ulId') != '') {
            $params ['ul_id'] = $request->param('ulId');
        }

        $userLogic     = Loader::model('User', 'logic');
        $userLevelInfo = $userLogic->getLevelPayConfig($params);
        $userLevelInfo = $this->_packLevelConfigInfo($userLevelInfo);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userLevelInfo)
        ];
    }

    /**
     * 编辑会员层级支付配置
     *
     * @param Request $request
     * @param  $ulId 用户层级id
     * @return array
     */
    public function editLevelPayConfig(Request $request,$ulId = '') {
        $params ['pc_id']                               = $request->param('id');
        $params ['ul_id']                               = $ulId ? $ulId : $request->param('ulId');
        $params ['pc_everyday_withdraw_count']          = $request->param('everydayWithdrawCount');
        $params ['pc_repeat_withdraw_time']             = $request->param('repeatWithdrawTime');
        $params ['pc_everyday_withdraw_free_count']     = $request->param('everydayWithdrawFreeCount');
        $params ['pc_everyday_withdraw_max_amount']     = $request->param('everydayWithdrawMaxAmount');
        $params ['pc_everytime_withdraw_max_amount']    = $request->param('everytimeWithdrawMaxAmount');
        $params ['pc_everytime_withdraw_min_amount']    = $request->param('everytimeWithdrawMinAmount');
        $params ['pc_withdraw_fee']                     = $request->param('withdrawFee');
        $params ['pc_online_discount_start_amount']     = $request->param('onlineDiscountStartAmount');
        $params ['pc_company_discount_start_amount']    = $request->param('companyDiscountStartAmount');
        $params ['pc_artificial_discount_start_amount'] = $request->param('artificialDiscountStartAmount');
        $params ['pc_online_discount_percentage']       = $request->param('onlineDiscountPercentage');
        $params ['pc_company_discount_percentage']      = $request->param('companyDiscountPercentage');
        $params ['pc_artificial_discount_percentage']   = $request->param('artificialDiscountPercentage');
        $params ['pc_online_recharge_max_amount']       = $request->param('onlineRechargeMaxAmount');
        $params ['pc_company_recharge_max_amount']      = $request->param('companyRechargeMaxAmount');
        $params ['pc_artificial_recharge_max_amount']   = $request->param('artificialRechargeMaxAmount');
        $params ['pc_online_recharge_min_amount']       = $request->param('onlineRechargeMinAmount');
        $params ['pc_company_recharge_min_amount']      = $request->param('companyRechargeMinAmount');
        $params ['pc_artificial_recharge_min_amount']   = $request->param('artificialRechargeMinAmount');
        $params ['pc_online_discount_max_amount']       = $request->param('onlineDiscountMaxAmount');
        $params ['pc_company_discount_max_amount']      = $request->param('companyDiscountMaxAmount');
        $params ['pc_artificial_discount_max_amount']   = $request->param('artificialDiscountMaxAmount');
        $params ['pc_recharge_traffic_mutiple']         = $request->param('rechargeTrafficMutiple');
        $params ['pc_discount_traffic_mutiple']         = $request->param('discountTrafficMutiple');
        $params ['pc_relax_amount']                     = $request->param('relaxAmount');
        $params ['pc_check_service_charge']             = $request->param('checkServiceCharge');
        $params ['pc_company_everyday_large_amount']    = $request->param('companyEverydayLargeAmount');
        $params ['pc_online_everyday_large_amount']     = $request->param('onlineEverydayLargeAmount');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editLevelPayConfig($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    private function _packUserList($info) {
        $data = [
            'uid'                     => $info ['user_id'],
            'username'                => $info ['user_name'],
            'nickname'                => $info ['user_nickname'],
            'realname'                => $info ['user_realname'],
            'parentUsername'          => $info ['parent_user_name'],
            'email'                   => $info ['user_email'],
            'mobile'                  => $info ['user_mobile'],
            'ul_id'                   => $info ['ul_id'],
            'ul_name'                 => $info ['ul_name'],
            'user_grade'              => $info ['user_grade'],
            'user_lower_count'        => $info ['user_lower_count'],
            'user_agent_check_status' => $info ['user_agent_check_status'],
            'user_is_agent'           => $info ['user_is_agent'],
            'user_contact_info'       => $info ['user_contact_info'],
            'account_balance'         => $info ['ue_account_balance'],
            'user_createtime'         => $info ['user_createtime'],
            'user_reg_ip'             => $info ['user_reg_ip'],
            'lastLoginTime'           => $info ['user_last_login_time'],
            'lastLoginIp'             => $info ['user_last_login_ip'],
            'remark'                  => $info ['user_remark'],
            'status'                  => $info ['user_status'],
            'user_level_status'       => $info ['user_level_status'],
            'reg_terminal'            => $info ['reg_terminal'],
            'qq'                      => $info ['user_qq'],
            'discountAmount'          => $info ['ue_discount_amount'],
            'rechargeAmount'          => $info ['ue_recharge_amount'],
            'withdrawAmount'          => $info ['ue_withdraw_amount'],
        ];
        isset($info['online_status']) && $data['online_status'] = $info['online_status'];
        return $data;
    }

    private function _packUserInfo($info) {
        return [
            'uid'                     => $info ['user_id'],
            'username'                => $info ['user_name'],
            'nickname'                => $info ['user_nickname'],
            'realname'                => $info ['user_realname'],
            'parentUsername'          => $info ['parent_user_name'],
            'email'                   => $info ['user_email'],
            'mobile'                  => $info ['user_mobile'],
            'qq'                      => $info ['user_qq'],
            'ul_id'                   => $info ['ul_id'],
            'user_grade'              => $info ['user_grade'],
            'user_lower_count'        => $info ['user_lower_count'],
            'user_agent_check_status' => $info ['user_agent_check_status'],
            'user_is_agent'           => $info ['user_is_agent'],
            'user_contact_info'       => $info ['user_contact_info'],
            'user_createtime'         => $info ['user_createtime'],
            'user_reg_ip'             => $info['user_reg_ip'],
            'lastLoginTime'           => $info ['user_last_login_time'],
            'lastLoginIp'             => $info ['user_last_login_ip'],
            'remark'                  => $info ['user_remark'],
            'status'                  => $info ['user_status'],
            'user_level_status'       => $info ['user_level_status'],
            'reg_terminal'            => $info ['reg_terminal'],
            'reg_way'                 => $info ['reg_way'],
            'channelName'             => $info ['channel_name'],
        ];
    }

    private function _packUserLoginLogInfo($info) {
        return [
            'uid'        => $info ['user_id'],
            'username'   => $info ['user_name'],
            'type'       => Config::get('status.user_login_type_name') [$info ['ull_type']],
            'loginTime'  => $info ['ull_login_time'],
            'modifytime' => $info['ull_modifytime'],
            'loginIp'    => $info ['ull_login_ip'],
            'country'    => $info['ull_country'],
            'region'     => $info['ull_region'],
            'city'       => $info['ull_city'],
        ];
    }

    private function _packUserBankList($info) {
        return [
            'id'           => $info ['ub_id'],
            'uid'          => $info ['user_id'],
            'bankId'       => $info ['bank_id'],
            'bankName'     => $info ['bank_name'],
            'bankAccount'  => $info ['ub_bank_account'],
            'bankUserName' => $info ['ub_bank_user_name'],
            'address'      => $info ['ub_address'],
            'status'       => $info ['ub_status']
        ];
    }

    private function _packUserBankInfo($info) {
        return [
            'id'           => $info ['ub_id'],
            'uid'          => $info ['user_id'],
            'bankId'       => $info ['bank_id'],
            'bankName'     => $info ['bank_name'],
            'bankAccount'  => $info ['ub_bank_account'],
            'bankUserName' => $info ['ub_bank_user_name'],
            'address'      => $info ['ub_address'],
            'remark'       => $info ['user_remark'],
            'status'       => $info ['ub_status']
        ];
    }

    private function _packUserRebateInfo($info) {
        return [
            'categoryId'        => $info ['lottery_category_id'],
            'categoryMaxRebate' => $info ['lottery_category_max_rebate'],
            'categoryName'      => $info ['lottery_category_name'],
            'userRebate'        => $info ['user_rebate']
        ];
    }

    private function _packUserStatistics($info) {
        return [
            'accountBalance' => $info ['ue_account_balance'],
            'discountAmount' => $info ['ue_discount_amount'],
            'rechargeAmount' => $info ['ue_recharge_amount'],
            'withdrawAmount' => $info ['ue_withdraw_amount'],
            'withdrawCount'  => $info ['ue_withdraw_count'],
            'rechargeCount'  => $info ['ue_recharge_count'],
            'loginCount'     => $info ['ue_login_count'],
            'lastLoginTime'  => $info ['user_last_login_time']
        ];
    }

    private function _packAccountInfo($info) {
        return [ 
                'username' => $info ['user_name'],
                'actionType' => Config::get ( 'status.account_record_action_type_name_for_client' ) [$info ['uar_transaction_type']],
                'transactionType' => Config::get ( 'status.account_record_transaction_type_name' ) [$info ['uar_transaction_type']],
                'amount' => $info ['uar_amount'],
                'beforeBalance' => $info ['uar_before_balance'],
                'afterBalance' => $info ['uar_after_balance'],
                'createtime' => $info ['uar_createtime'],
                'finishtime' => $info ['uar_finishtime'],
                'remark' => $info ['uar_remark'] 
        ];
    }

    private function _packUserOrderInfo($info) {
        return [
            'categoryId'   => $info ['lottery_category_id'],
            'categoryName' => $info ['lottery_category_name'],
            'lotteryName'    => $info ['lottery_name'],
            'issueNo'      => $info ['issue_no'],
            'typeId'       => $info ['lottery_type_id'],
            'typeName'     => $info ['lottery_type_name'],
            'betAmount'    => $info ['order_bet_amount'],
            'winningBonus' => $info ['order_winning_bonus'],
            'betContent'    => $info ['order_bet_content'],
            'createtime'   => $info ['order_createtime'],
            'distributeTime' => $info ['order_distribute_time'],
            'betIp' => $info ['order_bet_ip'],
            'status'       => Config::get('status.lottey_order_status_name') [$info ['order_status']]
        ];
    }

    private function _packLevelConfigInfo($info) {
        return [
            'id'                            => $info ['pc_id'],
            'ulId'                          => $info ['ul_id'],
            'everydayWithdrawCount'         => $info ['pc_everyday_withdraw_count'],
            'repeatWithdrawTime'            => $info ['pc_repeat_withdraw_time'],
            'everydayWithdrawFreeCount'     => $info ['pc_everyday_withdraw_free_count'],
            'everydayWithdrawMaxAmount'     => $info ['pc_everyday_withdraw_max_amount'],
            'everytimeWithdrawMaxAmount'    => $info ['pc_everytime_withdraw_max_amount'],
            'everytimeWithdrawMinAmount'    => $info ['pc_everytime_withdraw_min_amount'],
            'withdrawFee'                   => $info ['pc_withdraw_fee'],
            'onlineDiscountStartAmount'     => $info ['pc_online_discount_start_amount'],
            'companyDiscountStartAmount'    => $info ['pc_company_discount_start_amount'],
            'artificialDiscountStartAmount' => $info ['pc_artificial_discount_start_amount'],
            'onlineDiscountPercentage'      => $info ['pc_online_discount_percentage'],
            'companyDiscountPercentage'     => $info ['pc_company_discount_percentage'],
            'artificialDiscountPercentage'  => $info ['pc_artificial_discount_percentage'],
            'onlineRechargeMaxAmount'       => $info ['pc_online_recharge_max_amount'],
            'companyRechargeMaxAmount'      => $info ['pc_company_recharge_max_amount'],
            'artificialRechargeMaxAmount'   => $info ['pc_artificial_recharge_max_amount'],
            'onlineRechargeMinAmount'       => $info ['pc_online_recharge_min_amount'],
            'companyRechargeMinAmount'      => $info ['pc_company_recharge_min_amount'],
            'artificialRechargeMinAmount'   => $info ['pc_artificial_recharge_min_amount'],
            'onlineDiscountMaxAmount'       => $info ['pc_online_discount_max_amount'],
            'companyDiscountMaxAmount'      => $info ['pc_company_discount_max_amount'],
            'artificialDiscountMaxAmount'   => $info ['pc_artificial_discount_max_amount'],
            'rechargeTrafficMutiple'        => $info ['pc_recharge_traffic_mutiple'],
            'discountTrafficMutiple'        => $info ['pc_discount_traffic_mutiple'],
            'relaxAmount'                   => $info ['pc_relax_amount'],
            'checkServiceCharge'            => $info ['pc_check_service_charge'],
            'companyEverydayLargeAmount'    => $info ['pc_company_everyday_large_amount'],
            'onlineEverydayLargeAmount'     => $info ['pc_online_everyday_large_amount'],
        ];
    }


    public function lockUserLevel(Request $request) {
        $params    = $request->param();
        $userLogic = Loader::model('User', 'logic');

        $userLogic->lockUserLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
        ];

    }

    /**
     * 编辑用户真实姓名
     *
     * @param Request $request
     * @return array
     */
    public function editUserRealName(Request $request) {
        $params ['user_id']   = $request->param('uid');
        $params ['user_realname'] = trim($request->param('realname'));

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editUserRealName($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑用户资金密码
     *
     * @param Request $request
     * @return array
     */
    public function editUserFundsPassword(Request $request) {
        $params ['user_id']   = $request->param('uid');
        $params ['user_funds_password'] = $request->param('fundsPassword');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editUserFundsPassword($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑用户自身层级
     *
     * @param Request $request
     * @return array
     */
    public function editUserSelfLevel(Request $request) {
        $params ['user_id']   = $request->param('uid/a');
        $params ['ul_id'] = $request->param('ulId');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editUserSelfLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑用户列表层级(会员层级-会员人数)
     *
     * @param Request $request
     * @return array
     */
    public function editUserListLevel(Request $request) {
        $params ['user_id']   = $request->param('uid/a');
        $params ['ul_id'] = $request->param('ulId');

        $userLogic = Loader::model('User', 'logic');
        $result    = $userLogic->editUserListLevel($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 设置返水比例
     * @param ulId
     * @param proportion
     */
    public function setSportRebate(Request $request){
        $params['ul_id']      = $request->param('id');
        $params['proportion'] = $request->param('proportion');
        $userLogic = Loader::model('User','logic');
        $result    = $userLogic->setSportRebate($params);
        return[
             'errorcode' => $userLogic->errorcode,
             'message'   => Config::get('errorcode') [$userLogic->errorcode],
        ];
    }

    /**
     * 获取用户体彩注单列表
     *
     * @param Request $request
     * @return array
     */
    public function getUserSportOrderList(Request $request) {
        $params ['so_user_id'] = $request->param('uid');
        $params ['page']    = $request->param('page', 1);
        $params ['num']     = $request->param('num', 10);

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('sportId') != '') {
            $params ['so_st_id'] = $request->param('sportId');
        }
        if($request->param('orderNo') != '') {
            $params ['so_no'] = $request->param('orderNo');
        }
        if($request->param('status') != '') {
            $params ['so_status'] = $request->param('status');
        }

        $userLogic     = Loader::model('User', 'logic');
        $userOrderList = $userLogic->getUserSportOrderList($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode],
            'data'      => output_format($userOrderList)
        ];
    }

    public function forceOffline(Request $request){
        $uid = $request->param('uid');
        $userLogic = Loader::model('User', 'logic');
        $userLogic->forceOffline($uid);
        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
        ];
    }

    public function refreshUserStatistics(Request $request){
        $uid = $request->param('uid');
        $userLogic     = Loader::model('User', 'logic');
        $userLogic->refreshUserStatistics($uid);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode') [$userLogic->errorcode]
        ];
    }

}
