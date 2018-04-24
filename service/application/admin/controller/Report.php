<?php

/**
 * 报表控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Report {


    protected function getAgentReport(Request $request){
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('username') != '') {
            $params ['user_name'] = trim( $request->param('username') );
        }
        if($request->param('uid') != '') {
            $params ['user_id'] = $request->param('uid');
        }
        if($request->param('newUid') != '') {
            $params ['new_user_id'] = $request->param('newUid');
        }
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Ymd', strtotime($request->param('startDate')));
        } else {
            $params ['start_date'] = date('Ymd', strtotime("-1 day"));
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date("Ymd", strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date("Ymd", strtotime($params ['start_date']) + 86399);
        }
        if($request->param('excel') != '') {
            $params ['excel'] = $request->param('excel');
            $reportLogic = Loader::model('UserDayAccountRecord', 'logic');
            $reportUrl  = $reportLogic->getReportList($params);
            return [
                'errorcode' => $reportLogic->errorcode,
                'message'   => Config::get('errorcode') [$reportLogic->errorcode],
                'data'      => ['reportUrl' => $reportUrl]
            ];
        }
        $reportLogic = Loader::model('UserDayAccountRecord', 'logic');
        $reportList  = $reportLogic->getReportList($params);

        foreach($reportList ['list'] as &$info) {
            $info = $this->_packAgentReportList($info);
        }

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    /**
     * 代理报表
     *
     * @param Request $request
     * @return array
     */
    public function getAgentReportList(Request $request) {
        return $this->getAgentReport($request);
    }


    /**
     * 代理报表(免登录，单独直接访问)
     *
     * @param Request $request
     * @return array
     */
    public function getAgentReportByNoLogin(Request $request) {

        //白名单
        $wip = ['202.131.84.133', '211.24.114.202', '60.198.152.14'];

        $cip = get_client_ip();

        if(!in_array($cip,$wip)){
            return [
                'errorcode' => EC_AD_AGENT_REPORT_NOT_AT_WHITE_IP,
                'message'   => Config::get('errorcode') [EC_AD_AGENT_REPORT_NOT_AT_WHITE_IP],
                'data'      => ''
            ];
        }

        return $this->getAgentReport($request);
    }



    /**
     * 出入汇总
     *
     * @param Request $request
     * @return array
     */
    public function getOutinReport(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('startDate') != '') {
            $params ['start_date'] = $request->param('startDate');
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = $request->param('endDate');
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        $reportLogic = Loader::model('Report', 'logic');
        $reportList  = $reportLogic->getOutinReport($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    /**
     * 出入账目汇总详情
     */
    public function getOutinDetail(Request $request) {
        $params ['page']    = $request->param('page', 1);
        $params ['num']     = $request->param('num', 10);
        $params ['type']    = $request->param('type');
        $params ['user_id'] = $request->param('uid');
        $params ['sortField'] = $request->param('sortField');
        $params ['sort'] = $request->param('sort', 1);
        $params ['account'] = $request->param('account', '');
         
        if($request->param('startDate') != '') {
            $params ['start_date'] = $request->param('startDate');
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = $request->param('endDate');
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        if ( $request->param('sort') == 1 ) {
            $params ['sort'] = 'asc';
        }else{
            $params ['sort'] = 'desc';
        }

        $params ['sortField'] = input_format( [$params ['sortField']], true )[0];
        
        $reportLogic = Loader::model('UserAccountRecord', 'logic');
        $reportList  = $reportLogic->detail($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    /**
     * 用户可用余额详情
     */
    public function accountBalance(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['username']  = $request->param('username', '');
        $params ['num']  = $request->param('num', 10);

        if($request->param('startDate') != '') {
            $params ['start_date'] = $request->param('startDate');
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = $request->param('endDate');
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        $reportLogic = Loader::model('UserAccountRecord', 'logic');
        $reportList  = $reportLogic->accountBalance($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    /**
     * 用户未结算金额详情
     */
    public function betBalance(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);
        $params ['username']  = $request->param('username', '');

        if($request->param('startDate') != '') {
            $params ['start_date'] = $request->param('startDate');
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = $request->param('endDate');
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        $reportLogic = Loader::model('UserAccountRecord', 'logic');
        $reportList  = $reportLogic->betBalance($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    /**
     * 出入账目汇总详情列表
     */
    public function getOutinDetailList(Request $request) {
        $params ['page']    = $request->param('page', 1);
        $params ['num']     = $request->param('num', 10);
        $params ['user_id'] = $request->param('uid');
        $params ['type']    = $request->param('type');

        if($request->param('urrRechargeAccountId') != '') {
            $params ['urr_recharge_account_id'] = $request->param('urrRechargeAccountId');
        }

        if($request->param('startDate') != '') {
            $params ['start_date'] = $request->param('startDate');
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = $request->param('endDate');
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        $reportLogic = Loader::model('UserAccountRecord', 'logic');
        $reportList  = $reportLogic->detailList($params);

        foreach($reportList ['list'] as &$info) {
            $info = $this->_packDetailList($info);
        }

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }


    private function _packDetailList($info) {
        return [
            'ulName'          => $info ['ul_name'],
            'username'        => $info ['user_name'],
            'userRealname'    => $info ['user_realname'],
            'transactionType' => $info ['uar_transaction_type'],
            'amount'          => $info ['uar_amount'],
            'beforeBalance'   => $info ['uar_before_balance'],
            'afterBalance'    => $info ['uar_after_balance'],
            'createtime'      => $info ['uar_createtime'],
            'remark'          => $info ['uar_remark']
        ];
    }


    /**
     * 导出execl
     * @param Request $request
     */
    public function exportExcel(Request $request) {
        $reportType = $request->param('reportType');

        $startDate = $request->param('startDate');
        $endDate   = $request->param('endDate');

        if(count_days($startDate, $endDate) > 31) {
            return [
                'errorcode' => EC_AD_REPORT_DAY_LIMIT_ERROR,
                'message'   => Config::get('errorcode') [EC_AD_REPORT_DAY_LIMIT_ERROR]
            ];
        }

        $reportLogic = Loader::model('Report', 'logic');

        if($reportType == 1) {

            $reportUrl = $reportLogic->generateRechargeReport($request->param());

        } elseif($reportType == 2) {

            $reportUrl = $reportLogic->generateWithdrawReport($request->param());

        } elseif($reportType == 3){
            //首次充值
            $reportUrl  = $reportLogic->generateFirstRechargeReport($startDate,$endDate);

        } elseif($reportType == 4){
            //首次提现
            $reportUrl  = $reportLogic->generateFirstWithdrawReport($startDate,$endDate);
        }

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => ['reportUrl' => $reportUrl],
        ];
    }

    /**
     * 支付平台入款汇总
     *
     * @param Request $request
     * @return array
     */
    public function getPayPlatformRechargeReport(Request $request) {

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        $reportLogic = Loader::model('Report', 'logic');
        $reportList  = $reportLogic->getPayPlatformRechargeReport($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    private function _packAgentReportList($info) {
        return [
            'id'                   => $info ['usda_id'],
            'uid'                  => $info ['user_id'],
            'regTime'              => $info ['usercreatetime'],
            'username'             => $info ['username'],
            'recharge'             => $info ['recharge'],
            'withdraw'             => $info ['withdraw'],
            'deduction'            => $info ['deduction'],
            'bet'                  => $info ['bet'],
            'betCount'             => $info ['betCount'],
            'bonus'                => $info ['bonus'],
            'rebate'               => $info ['rebate'],
            'agentRebate'          => $info ['agentRebate'],
            'discount'             => $info ['discount'],
            'teamProfit'           => $info ['teamProfit'],
            'platformProfit'       => $info ['platformProfit'],
            'platformActualProfit' => $info ['platformActualProfit'],
            'rechargeNum'          => $info ['rechargeNum'],
            'withdrawNum'          => $info ['withdrawNum'],
            'agentCount'           => $info ['agentCount'],
            'userCount'            => $info ['userCount'],
            'betPeople'            => !empty($info ['betPeople'])?$info ['betPeople']:0,
        ];
    }

    /**
     * 导出详情execl
     */
    public function exportDetailExcel(Request $request) {
        $params ['page']    = $request->param('page', 1);
        $params ['num']     = $request->param('num', 10000);
        $params ['user_id'] = $request->param('uid');
        $params ['type']    = $request->param('type');


        if($request->param('urrRechargeAccountId') != '') {
            $params ['urr_recharge_account_id'] = $request->param('urrRechargeAccountId');
        }

        if($request->param('startDate') != '') {
            $params ['start_date'] = $request->param('startDate');
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = $request->param('endDate');
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        if(count_days($params ['start_date'], $params ['end_date']) > 30) {
            return [
                'errorcode' => EC_AD_REPORT_DAY_LIMIT_ERROR,
                'message'   => Config::get('errorcode') [EC_AD_REPORT_DAY_LIMIT_ERROR]
            ];
        }

        $reportLogic = Loader::model('Report', 'logic');
        $reportUrl  = $reportLogic->exportDetailExcel($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => ['reportUrl' => $reportUrl],
        ];
    }

    /**
     * 体彩代理报表
     *
     * @param Request $request
     * @return array
     */
    public function getSportAgentReportList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('username') != '') {
            $params ['user_name'] = trim( $request->param('username') );
        }
        if($request->param('uid') != '') {
            $params ['user_id'] = $request->param('uid');
        }
        if($request->param('newUid') != '') {
            $params ['new_user_id'] = $request->param('newUid');
        }
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Ymd', strtotime($request->param('startDate')));
        } else {
            $params ['start_date'] = date('Ymd', strtotime("-1 day"));
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date("Ymd", strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date("Ymd", strtotime($params ['start_date']) + 86399);
        }
        if($request->param('excel') != '') {
            $params ['excel'] = $request->param('excel');
            $reportLogic = Loader::model('UserDayAccountRecord', 'logic');
            $reportUrl  = $reportLogic->getSportReportList($params);
            return [
                'errorcode' => $reportLogic->errorcode,
                'message'   => Config::get('errorcode') [$reportLogic->errorcode],
                'data'      => ['reportUrl' => $reportUrl]
            ];
        }
        $reportLogic = Loader::model('UserDayAccountRecord', 'logic');
        $reportList  = $reportLogic->getSportReportList($params);

        foreach($reportList ['list'] as &$info) {
            $info = $this->_packSportAgentReportList($info);
        }

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    private function _packSportAgentReportList($info) {
        return [
            'id'                   => $info ['id'],
            'uid'                  => $info ['user_id'],
            'regTime'              => $info ['usercreatetime'],
            'username'             => $info ['username'],
            'recharge'             => $info ['recharge'],
            'withdraw'             => $info ['withdraw'],
            'deduction'            => $info ['deduction'],
            'bet'                  => $info ['bet'],
            'betCount'             => $info ['betCount'],
            'bonus'                => $info ['bonus'],
            'rebate'               => $info ['rebate'],
            'agentRebate'          => $info ['agentRebate'],
            'discount'             => $info ['discount'],
            'teamProfit'           => $info ['teamProfit'],
            'platformProfit'       => $info ['platformProfit'],
            'platformActualProfit' => $info ['platformActualProfit'],
            'rechargeNum'          => $info ['rechargeNum'],
            'withdrawNum'          => $info ['withdrawNum'],
            'agentCount'           => $info ['agentCount'],
            'userCount'            => $info ['userCount'],
            'betPeople'            => !empty($info ['betPeople'])?$info ['betPeople']:0,
        ];
    }

    /**
     * 体彩会员资料报表
     *
     * @param Request $request
     * @return array
     */
    public function getSportUserReportList(Request $request) {

        if($request->param('username') != '') {
            $params ['user_name'] = trim( $request->param('username') );
        }

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Ymd', strtotime($request->param('startDate')));
        } else {
            $params ['start_date'] = date('Ymd', strtotime("-1 day"));
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date("Ymd", strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date("Ymd", strtotime($params ['start_date']) + 86399);
        }

        if(count_days($params ['start_date'], $params ['end_date']) > 32) {
            return [
                'errorcode' => EC_AD_REPORT_DAY_LIMIT_ERROR,
                'message'   => Config::get('errorcode') [EC_AD_REPORT_DAY_LIMIT_ERROR]
            ];
        }

        if($request->param('excel') != '') {
            $params ['excel'] = $request->param('excel');
            $reportLogic = Loader::model('UserDayAccountRecord', 'logic');
            $reportUrl  = $reportLogic->userOrderAmountList($params);
            return [
                'errorcode' => $reportLogic->errorcode,
                'message'   => Config::get('errorcode') [$reportLogic->errorcode],
                'data'      => ['reportUrl' => $reportUrl]
            ];
        }
        
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );


        $reportLogic = Loader::model('UserDayAccountRecord', 'logic');
        $reportList  = $reportLogic->userOrderAmountList($params);

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }
}
