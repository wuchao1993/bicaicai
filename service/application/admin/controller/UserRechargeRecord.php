<?php

/**
 * 用户入款控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Collection;
use think\Request;
use think\Loader;
use think\Config;
use curl\Curlrequest;

class UserRechargeRecord
{

    /**
     * 获取人工入款列表
     *
     * @param Request $request
     * @return array
     */
    public function getSystemList(Request $request)
    {
        $params ['page'] = $request->param('page',1);
        $params ['num'] = $request->param('num',10);

        if ($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if ($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if ($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if ($request->param('operationType') != '') {
            $params ['urr_operation_type'] = $request->param('operationType');
        }
        if ($request->param('accountType') != '') {
            $params ['account_type'] = $request->param('accountType');
        }
        if ($request->param('accountValue') != '') {
            $params ['account_value'] = $request->param('accountValue');
        }

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $systemList = $userRechargeRecordLogic->getSystemList($params);
        foreach ($systemList ['list'] as &$info) {
            $info = $this->_packUserSystemInfo($info);
        }

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => $systemList
        ];
    }

    /**
     * 新增人工入款
     *
     * @param Request $request
     * @return array
     */
    public function addSystem(Request $request)
    {
        $params ['user_name'] = $request->param('username');
        $params ['urr_amount'] = $request->param('amount',0);
        $params ['urr_recharge_discount'] = $request->param('rechargeDiscount',0);
        $params ['urr_traffic_amount'] = $request->param('trafficAmount',0);
        $params ['urr_operation_type'] = $request->param('operationType',1);
        $params ['urr_remark'] = $request->param('remark','');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $systemInfo = $userRechargeRecordLogic->addSystem($params);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => output_format($systemInfo)
        ];
    }

    /**
     * 人工入款修改备注
     *
     * @param Request $request
     * @return array
     */
    public function editRemark(Request $request)
    {
        $params ['urr_no'] = $request->param('no');
        $params ['urr_remark'] = $request->param('remark','');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $systemInfo = $userRechargeRecordLogic->editRemark($params);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => output_format($systemInfo)
        ];
    }

    /**
     * 获取注单状态
     *
     * @return array
     */
    public function getSystemTypeList()
    {
        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');

        $data = array();
        foreach (Config::get('status.user_recharge_system_name') as $key => $val) {
            $data [$key - 1] = array(
                'id' => $key,
                'value' => $val
            );
        }

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => output_format($data)
        ];
    }


    /**
     * 清理优惠
     * @param Request $request
     */
    public function cleanDiscount(Request $request){
        $params['urr_id'] = (int) $request->param('id');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $result = $userRechargeRecordLogic->cleanDiscount($params);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => []
        ];
    }

    /**
     * 打码量设置
     * @param Request $request
     */
    public function setTraffic(Request $request){
        $params['urr_id']               = (int) $request->param('id');
        $params['urr_traffic_amount']   = $request->param('trafficAmount','0.00');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $result = $userRechargeRecordLogic->setTraffic($params);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => []
        ];
    }



    /**
     * 获取公司入款列表
     *
     * @param Request $request
     * @return array
     */
    public function getCompanyList(Request $request)
    {
        $params ['page'] = $request->param('page',1);
        $params ['num'] = $request->param('num',10);

        if ($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }
        if ($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }
        if ($request->param('minAmount') != '') {
            $params ['min_amount'] = $request->param('minAmount');
        }
        if ($request->param('maxAmount') != '') {
            $params ['max_amount'] = $request->param('maxAmount');
        }
        if ($request->param('dateType') != '') {
            $params ['date_type'] = $request->param('dateType');
        }
        if ($request->param('accountType') != '') {
            $params ['account_type'] = $request->param('accountType');
        }
        if ($request->param('accountValue') != '') {
            $params ['account_value'] = $request->param('accountValue');
        }
        if (!empty($request->param('rechargeAccountId/a'))) {
            $params ['urr_recharge_account_id'] = $request->param('rechargeAccountId/a');
        }
        if ($request->param('status') != '') {
            $params ['urr_status'] = $request->param('status');
        }
        if (!empty($request->param('ulIds/a'))) {
            $params ['ul_ids'] = $request->param('ulIds/a');
        }

        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
        if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']) {
            $rechargeLogic = Loader::model('CompanyRecharge', 'logic');
            $companyList = $rechargeLogic->getList($params);
        }else {
            $rechargeLogic = Loader::model('UserRechargeRecord', 'logic');
            $companyList = $rechargeLogic->getCompanyList($params);
        }

        foreach ($companyList ['list'] as &$info) {
            $info = $this->_packUserCompanyInfo($info);
        }

        return [
            'errorcode' => $rechargeLogic->errorcode,
            'message' => Config::get('errorcode') [$rechargeLogic->errorcode],
            'data' => $companyList
        ];
    }

    /**
     * 导出execl
     * @param Request $request
     */
    public function exportExcel(Request $request){

        $startDate       = $request->param('startDate');
        $endDate         = $request->param('endDate');

        if(count_days($startDate, $endDate) > 31) {
            return [
                'errorcode' => BC_AD_WITHDRAW_EXPORT_EXCEL_DAY_LIMIT_ERROR,
                'message'   => Config::get('errorcode') [BC_AD_WITHDRAW_EXPORT_EXCEL_DAY_LIMIT_ERROR]
            ];
        }

        $params ['start_date'] = !empty($startDate)?date('Y-m-d 00:00:00', strtotime($request->param('startDate'))):date('Y-m-d 00:00:00');
        $params ['end_date']   = !empty($endDate)?date('Y-m-d 23:59:59', strtotime($request->param('endDate'))):date('Y-m-d 23:59:59');

        if ($request->param('minAmount') != '') {
            $params ['min_amount'] = $request->param('minAmount');
        }
        if ($request->param('maxAmount') != '') {
            $params ['max_amount'] = $request->param('maxAmount');
        }
        if ($request->param('dateType') != '') {
            $params ['date_type'] = $request->param('dateType');
        }
        if ($request->param('accountType') != '') {
            $params ['account_type'] = $request->param('accountType');
        }
        if ($request->param('accountValue') != '') {
            $params ['account_value'] = $request->param('accountValue');
        }
        if (!empty($request->param('rechargeAccountId/a'))) {
            $params ['urr_recharge_account_id'] = $request->param('rechargeAccountId/a');
        }
        if ($request->param('status') != '') {
            $params ['urr_status'] = $request->param('status');
        }
        if (!empty($request->param('ulIds/a'))) {
            $params ['ul_ids'] = $request->param('ulIds/a');
        }

        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
        if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']) {
            $rechargeLogic = Loader::model('CompanyRecharge', 'logic');
            $companyList = $rechargeLogic->getList($params);
        }else {
            $rechargeLogic = Loader::model('UserRechargeRecord', 'logic');
            $companyList = $rechargeLogic->getCompanyList($params);
        }

        $list = $companyList['list'] ? collection($companyList['list'])->toArray() : [];

        if(!empty($list)){
            $tmp = [];
            $data = [];
            foreach ($list as $item){
                $tmp['ul_name']         = $item['ul_name'];
                $tmp['user_name']       = $item['user_name'];
                $tmp['urr_recharge_bank']   = $item['urr_recharge_user_name'].'_'.$item['bank_name'];
                $tmp['parent_user_name'] = $item['parent_user_name'];
                $tmp['recharge_account_bank']     = $item['recharge_account_name'].'_'.$item['recharge_account_bank_name'];
                $tmp['recharge_amount']= $item['urr_amount'] . '(+'. $item['urr_recharge_discount'] . ')';
                $tmp['urr_createtime']   = $item ['urr_createtime'];
                $tmp['urr_recharge_time']   = $item ['urr_recharge_time'];
                $tmp['urr_confirm_time']   = $item ['urr_confirm_time'];
                $tmp['urr_remark']   = $item ['urr_remark'];
                $tmp['urr_reason']   = $item ['urr_reason'];
                $tmp['operator_name']      = $item['operator_name'];
                $tmp['urr_status']      = $item ['urr_status'] == 1 ? '充值成功' : ( $item ['urr_status'] == 0 ? '待处理' : '充值失败' ) ;
                $data[] = $tmp;
            }
        }

        $fileName = 'user_recharge_company_'.$startDate.'-'.$endDate;
        $title = ['会员层级','会员账号','会员存款银行账户','上级用户','存入账号','存入金额','系统提交时间','会员填写时间','确定时间','备注','取消理由','操作人','状态'];

        $localFilePath  = 'uploads' . DS . $fileName;
        Loader::model('ReportExcel', 'logic')->ExportList($data, $title, $localFilePath);
        $ossFileName = $localFilePath.'.xls';

        return [
            'errorcode' => $rechargeLogic->errorcode,
            'message'   => Config::get('errorcode') [$rechargeLogic->errorcode],
            'data'      => ['reportUrl' => $ossFileName],
        ];

    }

    /**
     * 公司入款确认
     *
     * @param Request $request
     * @return array
     */
    public function confirmCompany(Request $request)
    {
        $params ['urr_id'] = $request->param('id');

        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
        if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']) {
            $rechargeLogic = Loader::model('CompanyRecharge', 'logic');
            $confirmInfo = $rechargeLogic->confirm($params);
        }else {
            $rechargeLogic = Loader::model('UserRechargeRecord', 'logic');
            $confirmInfo = $rechargeLogic->confirmCompany($params);
        }

        return [
            'errorcode' => $rechargeLogic->errorcode,
            'message' => Config::get('errorcode') [$rechargeLogic->errorcode],
            'data' => output_format($confirmInfo)
        ];
    }

    /**
     * 公司入款取消
     *
     * @param Request $request
     * @return array
     */
    public function cancelCompany(Request $request)
    {
        $params['urr_id']      = $request->param('id');
        $params['urr_reason']  = $request->param('reason');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $confirmInfo = $userRechargeRecordLogic->cancelCompany($params);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => output_format($confirmInfo)
        ];
    }

    /**
     * 公司入款恢复取消
     *
     * @param Request $request
     * @return array
     */
    public function reCancelCompany(Request $request)
    {
        $params ['urr_id'] = $request->param('id');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');
        $confirmInfo = $userRechargeRecordLogic->reCancelCompany($params);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
            'data' => output_format($confirmInfo)
        ];
    }

    /**
     * 获取在线入款列表
     *
     * @param Request $request
     * @return array
     */
    public function getOnlineList(Request $request)
    {
        $params ['page'] = $request->param('page',1);
        $params ['num'] = $request->param('num',10);

        if ($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }else{
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }
        if ($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }else{
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }
        if ($request->param('minAmount') != '') {
            $params ['min_amount'] = $request->param('minAmount');
        }
        if ($request->param('maxAmount') != '') {
            $params ['max_amount'] = $request->param('maxAmount');
        }
        if ($request->param('dateType') != '') {
            $params ['date_type'] = $request->param('dateType');
        }
        if ($request->param('accountType') != '') {
            $params ['account_type'] = $request->param('accountType');
        }
        if ($request->param('accountValue') != '') {
            $params ['account_value'] = $request->param('accountValue');
        }
        if ($request->param('rechargeAccountId/a') != '') {
            $params ['urr_recharge_account_id'] = $request->param('rechargeAccountId/a');
        }
        if ($request->param('status') != '') {
            $params ['urr_status'] = $request->param('status');
        }
        if ($request->param('ulIds/a') != '') {
            $params ['ul_ids'] = $request->param('ulIds/a');
        }

        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
        if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']) {
            $logic = Loader::model('OnlineRechargeRecord', 'logic');
            $onlineList = $logic->getList($params);

            foreach ($onlineList ['list'] as &$info) {
                $info = $this->_packOnlineInfo($info);
            }

        }else {
            $logic = Loader::model('UserRechargeRecord', 'logic');
            $onlineList = $logic->getOnlineList($params);

            foreach ($onlineList ['list'] as &$info) {
                $info = $this->_packUserOnlineInfo($info);
            }
        }

        return [
            'errorcode' => $logic->errorcode,
            'message' => Config::get('errorcode') [$logic->errorcode],
            'data' => $onlineList
        ];
    }

    private function _packUserSystemInfo($info)
    {
        return [
            'no' => $info ['urr_no'],
            'username' => $info ['user_name'],
            'operationTypeName' => Config::get('status.user_recharge_system_name') [$info ['urr_operation_type']],
            'amount' => $info ['urr_amount'],
            'rechargeDiscount' => $info ['urr_recharge_discount'],
            'trafficAmount' => $info ['urr_traffic_amount'],
            'createtime' => $info ['urr_createtime'],
            'remark' => $info ['urr_remark'],
            'operatorName' => $info ['operator_name'],
            'isFirst' => $info ['urr_is_first']
        ];
    }

    private function _packUserCompanyInfo($info)
    {
        return [
            'id' => $info ['urr_id'],
            'ulName' => $info ['ul_name'],
            'no' => $info ['urr_no'],
            'uid' => $info ['user_id'],
            'username' => $info ['user_name'],
            'userRealname' => $info ['user_realname'],
            'parentUsername' => $info ['parent_user_name'],
            'bankName' => $info ['bank_name'],
            'detail' => $info ['urr_detail'],
            'rechargeUserName' => $info ['urr_recharge_user_name'],
            'amount' => $info ['urr_amount'],
            'rechargeDiscount' => $info ['urr_recharge_discount'],
            'totalAmount' => $info ['urr_total_amount'],
            'rechargeAccountBankName' => $info ['recharge_account_bank_name'],
            'rechargeAccountName' => $info ['recharge_account_name'],
            'rechargeTime' => $info ['urr_recharge_time'],
            'createtime' => $info ['urr_createtime'],
            'remark' => $info ['urr_remark'],
            'operatorName' => $info ['operator_name'],
            'status' => $info ['urr_status'],
            'confirmTime' => $info ['urr_confirm_time'],
            'reason' => $info ['urr_reason'],
            'userLargeAmount'  => $info['user_large_amount'],
            'transferType' => Config::get('status.company_recharge_type') [$info ['urr_transfer_type']]
        ];
    }

    private function _packUserOnlineInfo($info)
    {
        if(empty(Config::get('status.pay_category_type_name') [$info ['pp_category_id']])) {
            $payCategoryName = $info['pay_category_name'];
        }else{
            $payCategoryName = Config::get('status.pay_category_type_name') [$info ['pp_category_id']];
        }
        return [
            'id' => $info ['urr_id'],
            'ulName' => $info ['ul_name'],
            'no' => $info ['urr_no'],
            'uid' => $info ['user_id'],
            'username' => $info ['user_name'],
            'parentUsername' => $info ['parent_user_name'],
            'payPlatform' => $info ['recharge_platform'],
            'payCategoryName' => $payCategoryName,
            'amount' => $info ['urr_amount'],
            'rechargeDiscount' => $info ['urr_recharge_discount'],
            'createtime' => $info ['urr_createtime'],
            'tradetime' => $info ['urr_trade_time'],
            'remark' => $info ['urr_remark'],
            'operatorName' => $info ['operator_name'],
            'status' => Config::get('status.recharge_status_name') [$info ['urr_status']]
        ];
    }

    private function _packOnlineInfo($info)
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

    /**
     * 在线充值补单
     * @param Request $request
     * @return array
     */
    public function activeQuery(Request $request)
    {
        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
        Log::write('enforceOnlineRecharge-payCenterStatus:'. print_r($payCenterStatus,true));
        if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']){
            $payCenterLogic = Loader::model('PayCenter', 'logic');
            $result = $payCenterLogic->activeQuery($request->post());
            return send_response($result, $payCenterLogic->errorcode);
        }else{
            $apiUrl = \think\Env::get('app.digital_api_url');
            $orderId = $request->param('orderId');
            $params = [
                'act' => DIGITAL_BUDAN_ACTION,
                'order_id' => $orderId
            ];

            $sign = generate_digital_sign($params);

            $data = json_encode($params);

            $header = [
                'Content-Type: text/json',
                "Content-length: " . strlen($data),
                "Authorization: " . $sign
            ];

            $curlRequest = new Curlrequest();
            $result = $curlRequest->curlJsonPost($apiUrl, $data, $header);
            $result = json_decode($result, true);
            $order_status = array('message' => $result['message']);
            return show_response(EC_SUCCESS,Config::get('errorcode') [EC_SUCCESS],$order_status);
        }
    }



    public function enforceOnlineRecharge(Request $request){

        $id = $request->param('orderId');

        $userRechargeRecordLogic = Loader::model('UserRechargeRecord', 'logic');

        $userRechargeRecordLogic->enforceOnlineRecharge($id);

        return [
            'errorcode' => $userRechargeRecordLogic->errorcode,
            'message' => Config::get('errorcode') [$userRechargeRecordLogic->errorcode],
        ];
    }


    public function importSystem(){

        $data = Loader::model("ImportExcel",'logic')->getExcelData();

        if(empty($data)){
            return show_response(EC_AD_EXCEL_NOT_DATA,Config::get('errorcode') [EC_AD_EXCEL_NOT_DATA]);
        }

        $userNames = extract_array($data,'0');
        $userNames = array_unique($userNames);
        $userInfos = Loader::model("User","model")->getUserIdByUserName($userNames);

        $operationType = Config::get("status.system_recharge");

        $postData = [];
        $errorUserLines = [];
        $errorTypeLines = [];
        foreach ($data as $key=>$row){

            if(!empty($row[0]) && isset($userInfos[$row[0]])){
                $postData[$key]['user_id']              = $userInfos[$row[0]];
            }else{
                $errorUserLines[] = $key+1;
                continue;
            }

            $postData[$key]['urr_amount']               = $row[1];
            $postData[$key]['urr_recharge_discount']    = $row[2];
            $postData[$key]['urr_traffic_amount']       = $row[3];

            if(!empty($row[4]) && isset($operationType[$row[4]])){
                $postData[$key]['urr_operation_type']   = $operationType[$row[4]];
            }else{
                $errorTypeLines[] = $key+1;
                unset($postData[$key]);
                continue;
            }
            $postData[$key]['urr_remark']               = $row[5];
        }

        $logic = Loader::model("UserRechargeRecord","logic");

        $count = count($postData);
        $logic->doBatchSystemAdd($postData);

        $data = [];
        if(!empty($errorUserLines) || !empty($errorTypeLines)){
            $data  = compact('errorUserLines','errorTypeLines');
            $errorCount = count($errorTypeLines) + count($errorUserLines);
            return show_response(0,'错误'.$errorCount.'行，成功入款'.$count.'条,请返回列表对照。',$data);
        }

        return [
            'errorcode' => $logic->errorcode,
            'message'   => Config::get('errorcode') [$logic->errorcode],
            'data'      => $data
        ];
    }

}
